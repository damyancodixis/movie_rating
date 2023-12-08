<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\Review;
use App\Form\ReviewFormType;
use App\Repository\MovieRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ValidationErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MovieController extends AbstractController
{
    const MOVIES_PER_PAGE = 12;
    const REVIEWS_PER_PAGE = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MovieRepository $movieRepository,
        private ReviewRepository $reviewRepository,
        private SerializerInterface $serializer,
        private UserRepository $userRepository,
        private ValidationErrorHandler $validationErrorHandler
    ) {
    }

    #[Route('/movies', methods: ['GET'], name: 'homepage')]
    public function homePage(
        #[MapQueryParameter] string $page = '1',
        #[MapQueryParameter] string|null $title = null,
    ) {
        if (!ctype_digit($page) || $page < 1) {
            throw new BadRequestHttpException("Query parameter 'page' is invalid");
        }

        $paginatedResult = $this->movieRepository->findPaginated($page, self::MOVIES_PER_PAGE, $title);
        $totalPages = ceil(count($paginatedResult) / self::MOVIES_PER_PAGE);

        return $this->render(
            'pages/home.html.twig',
            [
                'movies' => $paginatedResult,
                'page' => $page,
                'perPage' => self::MOVIES_PER_PAGE,
                'totalItems' => count($paginatedResult),
                'totalPages' => $totalPages,
            ]
        );
    }

    #[Route('/movies/{id}', methods: ['GET', 'POST'], name: 'movie_details_page')]
    public function movieDetailsPage(
        Movie $movie,
        Request $request,
        #[MapQueryParameter] string $page = '1',
    ) {
        $userReview = null;
        $form = null;

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // Find existing review record by current user. If no record exists, create one.
            $username = $this->getUser()->getUserIdentifier();
            $user = $this->userRepository->findOneBy(['username' => $username]);
            $userReview = $this->reviewRepository->findOneBy([
                'createdBy' => $user->getId(),
                'movie' => $movie->getId(),
            ]);
            if (!$userReview) {
                $userReview = new Review();
                $userReview->setMovie($movie);
            }

            $form = $this->createForm(
                ReviewFormType::class,
                $userReview,
                [
                    'action' => $this->generateUrl('movie_details_page', ['id' => $movie->getId()]),
                ]
            );
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                // Determine if the request is for the dynamic fields. If it is, do nothing.
                $isChangeViewRequest = $request->request->get('changeViewRequest');

                if (!$isChangeViewRequest) {
                    $userReview = $form->getData();

                    // Check if user submitted only a rating
                    if (!$form->get('isReview')->getData()) {
                        $userReview->setTitle(null);
                        $userReview->setContent(null);
                    }

                    $errors = $this->validationErrorHandler->getErrors($userReview);

                    if (count($errors) > 0) {
                        foreach ($errors as $error) {
                            $form->addError(new FormError($error));
                        }
                    } else {
                        $this->entityManager->persist($userReview);
                        $this->entityManager->flush();
                    }
                }
            }
        }
        else if ($request->getMethod() === Request::METHOD_POST) {
            throw new UnauthorizedHttpException('', 'Only registered users can rate movies');
        }

        $paginatedReviews = $this->reviewRepository
            ->findReviewsByMovie($movie->getId(), $page, self::REVIEWS_PER_PAGE);
        $totalPages = ceil(count($paginatedReviews) / self::MOVIES_PER_PAGE);

        return $this->render(
            'pages/movie.html.twig',
            [
                'movie' => $movie,
                'reviews' => $paginatedReviews,
                'userReview' => $userReview,
                'page' => $page,
                'perPage' => self::REVIEWS_PER_PAGE,
                'totalItems' => count($paginatedReviews),
                'totalPages' => $totalPages,
                'reviewForm' => $form,
            ]
        );
    }
}

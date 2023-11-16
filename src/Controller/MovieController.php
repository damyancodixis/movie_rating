<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\Review;
use App\Repository\MovieRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ValidationErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        private ValidatorInterface $validator,
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

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if ($request->isMethod(Request::METHOD_POST)) {
                $userReview = $this->rateMovie($movie, $request);
                if (!$userReview instanceof Review) {
                    return $this->json($userReview, 400);
                }

                return $this->json(null, 204);
            } else {
                $username = $this->getUser()->getUserIdentifier();
                $user = $this->userRepository->findOneBy(['username' => $username]);
                $userReview = $this->reviewRepository->findOneBy(['createdBy' => $user->getId(), 'movie' => $movie->getId()]);
            }
        }

        $paginatedReviews = $this->reviewRepository->findReviewsByMovie($movie->getId(), $page, self::REVIEWS_PER_PAGE);
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
            ]
        );
    }

    // Return type array is an array of all validation errors that occurred
    private function rateMovie(Movie $movie, Request $request): Review | array
    {
        $newRating = $request->getPayload()->get('rating');
        // Helper variable for calculations in case user updates his existing rating
        $oldRating = 0;
        $ratingsCount = $movie->getRatingsCount();

        $username = $this->getUser()->getUserIdentifier();
        $user = $this->userRepository->findOneBy(['username' => $username]);
        // Check if user has already submitted a review
        $review = $this->reviewRepository->findOneBy(['createdBy' => $user->getId(), 'movie' => $movie->getId()]);

        if (!$review) {
            $review = new Review();
            $review->setMovie($movie);
            $ratingsCount++;
        }
        else {
            $oldRating = $review->getRating();
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Review::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $review,
                'groups' => 'review'
            ],
        );

        $reviewErrors = $this->validationErrorHandler
            ->getValidationErrors($this->validator->validate($review));
        if (count($reviewErrors) > 0) {
            return $reviewErrors;
        }

        $calculatedRating =
            ($movie->getRating() * $movie->getRatingsCount() + $newRating - $oldRating) / $ratingsCount;

        $movie->setRatingsCount($ratingsCount);
        $movie->setRating($calculatedRating);

        $movieErrors = $this->validationErrorHandler
            ->getValidationErrors($this->validator->validate($movie));
        if (count($movieErrors) > 0) {
            return $movieErrors;
        }

        $this->entityManager->persist($movie);
        $this->entityManager->persist($review);
        $this->entityManager->flush();
        return $review;
    }
}

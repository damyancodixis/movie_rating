<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ReviewRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MovieController extends AbstractController
{
    const MOVIES_PER_PAGE = 12;
    const REVIEWS_PER_PAGE = 10;

    public function __construct(
        private MovieRepository $movieRepository,
        private ReviewRepository $reviewRepository,
        private SerializerInterface $serializer,
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
                // Must be the same as 'name' prop of #Route attribute
                'routeName' => 'homepage',
                // All params that come after base route
                'urlParams' => [],
            ]
        );
    }

    #[Route('/movies/{id}', methods: ['GET'], name: 'movie_details_page')]
    public function movieDetailsPage(
        string $id,
        #[MapQueryParameter] string $page = '1',
    ) {
        if (!Uuid::isValid($id)) {
            throw new BadRequestException("Invalid movie id");
        }

        $movie = $this->movieRepository->find($id);
        if (!$movie) {
            throw new NotFoundHttpException("No movie was found");
        }

        $paginatedReviews = $this->reviewRepository->findReviewsByMovie($id, $page, self::REVIEWS_PER_PAGE);
        $totalPages = ceil(count($paginatedReviews) / self::MOVIES_PER_PAGE);

        return $this->render(
            'pages/movie.html.twig',
            [
                'movie' => $movie,
                'reviews' => $paginatedReviews,
                'page' => $page,
                'perPage' => self::REVIEWS_PER_PAGE,
                'totalItems' => count($paginatedReviews),
                'totalPages' => $totalPages,
                // Must be the same as 'name' prop of #Route attribute
                'routeName' => 'homepage',
                // All params that come after base route
                'urlParams' => [
                    'id' => $id,
                ],
            ]
        );
    }
}

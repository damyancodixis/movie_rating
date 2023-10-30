<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MovieController extends AbstractController
{
    const MOVIES_PER_PAGE = 12;

    public function __construct(
        private MovieRepository $movieRepository,
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
                'totalPages' => $totalPages,
                // Must be the same as 'name' prop of #Route attribute
                'routeName' => 'homepage',
                'searchProp' => 'title',
                'totalItems' => count($paginatedResult)
            ]
        );
    }

    #[Route('/movies/{id}', methods: ['GET'], name: 'movie_details_page')]
    public function movieDetailsPage(string $id)
    {
        $movie = $this->movieRepository->find($id);

        if (!$movie) {
            throw new NotFoundHttpException();
        }

        return $this->render('movie.html.twig', ['movie' => $movie]);
    }
}

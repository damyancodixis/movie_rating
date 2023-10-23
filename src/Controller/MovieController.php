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

    #[Route('/movies', methods: ['GET'], name: 'movies_get_collection')]
    public function getCollection(#[MapQueryParameter] string $page = '1')
    {
        if (!ctype_digit($page)) {
            throw new BadRequestHttpException("Query parameter 'page' is missing or invalid");
        }

        $paginatedResult = $this->movieRepository->findPaginated($page, self::MOVIES_PER_PAGE);

        return $this->render(
            'home.html.twig',
            [
                'movies' => $paginatedResult,
                'page' => $page,
            ]
        );
    }

    #[Route('/movies/{id}', methods: ['GET'], name: 'movies_get_item')]
    public function getItem(string $id)
    {
        $movie = $this->movieRepository->find($id);

        if (!$movie) {
            throw new NotFoundHttpException();
        }

        return $this->render('movie.html.twig', ['movie' => $movie]);
    }
}

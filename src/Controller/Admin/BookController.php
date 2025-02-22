<?php

namespace App\Controller\Admin;

use App\DTO\BookFilter;
use App\Entity\Book;
use App\Entity\User;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Service\BookService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/book')]
class BookController extends AbstractController
{
    #[Route('', name: 'app_admin_book', methods: ['GET'])]
    public function index(Request $request, BookService $bookService, PaginationService $paginationService, BookRepository $bookRepository): Response
    {
        $filter = BookFilter::fromRequest($request->query->all());
        $books = $bookService->getAll($filter,  $request->query->getInt('page', 1), $paginationService, $bookRepository);
        return $this->render('admin/book/index.html.twig', [
            'books' => $books,
        ]);
    }

    #[IsGranted('ROLE_AJOUT_DE_LIVRE')]
    #[Route('/new', name: 'app_admin_book_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_admin_book_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(?Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {

        if ($book) {
            //$this->denyAccessUnlessGranted('book.is_creator', $book);
            $this->denyAccessUnlessGranted('ROLE_EDITION_DE_LIVRE');
        }

        $book ??= new Book();
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();
            if (!$book->getId() && $user instanceof User) {
                $book->setCreatedBy($user);
            }
            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_book');
        }

        return $this->render('admin/book/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_book_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(?Book $book): Response
    {
        return $this->render('admin/book/show.html.twig', [
            'book' => $book,
        ]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;


use App\Entity\Todo;
use App\Repository\TodoRepository;
use App\OptionsResolver\TodoOptionsResolver;

class TodoController extends AbstractController
{
    #[Route("/todos", "all_todos", methods: ["GET"])]
    public function todos(TodoRepository $todoRepository): JsonResponse
    {
        $todos = $todoRepository->findAll();

        return $this->json($todos);
    }

    #[Route("/todo/{id}", "get_todo", methods: ["GET"])]
    public function getTodo(Todo $todo): JsonResponse
    {
        if (!$todo) 
        {
            throw new BadRequestHttpException('No todo found for id ' . $id);
        }
        return $this->json($todo);
    }

    #[Route("/todo/{id}", "delete_todo", methods: ["DELETE"])]
    public function deleteTodo(Todo $todo, EntityManagerInterface $entityManager)
    {
        if (!$todo) 
        {
            throw new BadRequestHttpException('No todo found for id ' . $id);
        }
        $entityManager->remove($todo);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route("/todo", "create_todo", methods: ["POST"])]
    public function createTodo(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, TodoOptionsResolver $todoOptionsResolver): JsonResponse
    {
        try {
            $requestBody = json_decode($request->getContent(), true);
    
            $fields = $todoOptionsResolver->configureTitle(true)->resolve($requestBody);
            $todo = new Todo();
            $todo->setTitle($fields["title"]);
    
            $errors = $validator->validate($todo);
            if (count($errors) > 0) {
                throw new InvalidArgumentException((string) $errors);
            }
    
            $entityManager->persist($todo);
            $entityManager->flush();
    
            return $this->json($todo, status: Response::HTTP_CREATED);
        } catch(Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    #[Route("/todo/{id}", "update_todo", methods: ["PUT"])]
    public function updateTodo(Todo $todo, Request $request, TodoOptionsResolver $todoOptionsResolver, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        if (!$todo) 
        {
            throw new BadRequestHttpException('No todo found for id ' . $id);
        }
        try {
            $requestBody = json_decode($request->getContent(), true);
    
            $fields = $todoOptionsResolver
                ->configureTitle(true)
                ->configureCompleted(false)
                ->resolve($requestBody);
    
            foreach($fields as $field => $value) {
                switch($field) {
                    case "title":
                        $todo->setTitle($value);
                        break;
                    case "completed":
                        $todo->setCompleted($value);
                        break;
                }
            }
    
            $errors = $validator->validate($todo);
            if (count($errors) > 0) {
                throw new InvalidArgumentException((string) $errors);
            }
    
            $em->flush();
    
            return $this->json($todo);
        } catch(Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}

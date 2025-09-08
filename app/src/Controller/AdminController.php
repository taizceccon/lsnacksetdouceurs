<?php

namespace App\Controller;
use App\Entity\Category;
use App\Entity\Product;
use App\Form\CategoryForm;
use App\Form\ProductType;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository; 
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\RegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Document\Avis;
use Doctrine\ODM\MongoDB\DocumentManager;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_index')]
    public function indexAdmin(Request $request, CategoryRepository $categoryRepository, ProductRepository $productRepository, UserRepository $userRepository, OrderRepository $orderRepository, DocumentManager $dm): Response
    {
        $search = $request->query->get('search');
        $productSearch = $request->query->get('product_search');
        // Recherche des catégories
        $categories = $search
            ? $categoryRepository->createQueryBuilder('c')
                ->where('c.category LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->getQuery()
                ->getResult()
            : $categoryRepository->findAll();
        // Recherche des produits
        $products = $productSearch
            ? $productRepository->createQueryBuilder('p')
                ->where('p.titre LIKE :productSearch')
                ->setParameter('productSearch', '%' . $productSearch . '%')
                ->getQuery()
                ->getResult()
            : $productRepository->findAll();
        // Récupérer le nombre total des commandes
        $orderCount = $orderRepository->count([]);
        // Récupérer toutes les commandes
        // $orders = $orderRepository->findAll();
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']);
         // Récupérer le nombre total des avis (MongoDB)         
        $avisCount = $dm->createQueryBuilder(Avis::class)
        ->count()
        ->getQuery()
        ->execute();
         return $this->render('admin/dashboard/index.html.twig', [
            'categories' => $categories,
            'products' => $products,
            'search' => $search,
            'product_search' => $productSearch,
            'orderCount' => $orderCount,
            'productCount' => $productRepository->count([]),
            'categoryCount' => $categoryRepository->count([]),
            'userCount' => $userRepository->count([]),
            'users' => $userRepository->findAll(),
            'avisCount' => $avisCount,
            'orders' => $orders,
        ]);
    }

    #[Route('/admin/orders/{id}', name: 'admin_order_show', methods: ['GET', 'POST'])]
    public function adminShow(Order $order, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $newStatus = $request->request->get('status');

            if ($newStatus && $newStatus !== $order->getStatus()) {
                $order->setStatus($newStatus);
                $em->flush();

                // Préparer le contenu du mail selon le nouveau statut
                $subject = 'Mise à jour de votre commande #' . $order->getId();
                switch ($newStatus) {
                    case 'Refusé':
                        $body = '<p>Bonjour,</p><p>Votre commande #' . $order->getId() . ' a été refusée.</p>';
                        break;
                    case 'En cours':
                        $body = '<p>Bonjour,</p><p>Votre commande #' . $order->getId() . ' est en cours de traitement.</p>';
                        break;
                    case 'Envoyé':
                        $body = '<p>Bonjour,</p><p>Votre commande #' . $order->getId() . ' a été envoyée.</p>';
                        break;
                    case 'Validé':
                        $body = '<p>Bonjour,</p><p>Votre commande #' . $order->getId() . ' a été validée.</p>';
                        break;
                    case 'Reçu':
                        $body = '<p>Bonjour,</p><p>Votre commande #' . $order->getId() . ' a été reçue.</p>';
                        break;
                    default:
                        $body = '<p>Bonjour,</p><p>Le statut de votre commande #' . $order->getId() . ' a été mis à jour : ' . htmlspecialchars($newStatus) . '.</p>';
                        break;
                }

                $email = (new Email())
                    ->from('no-reply@leilasd.fr') //l’adresse d’expéditeur
                    ->to($order->getUser()->getEmail())
                    ->subject($subject)
                    ->html($body);

                $mailer->send($email);

                $this->addFlash('success', 'Statut mis à jour avec succès et email envoyé.');
                return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
            }
        }

        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

   #[Route('/admin/all', name: 'admin_order_index')]
    public function adminIndex(EntityManagerInterface $em): Response
    {
        $orders = $em->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/admin/produits', name: 'admin_products_index')]
    public function listProducts(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/admin/new', name: 'admin_product_new')]
    public function newProduct(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        dump($e->getMessage()); // <-- Affiche l'erreur exacte
                        $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                        return $this->redirectToRoute('admin_product_new');
                    }

                $product->setImage($newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('admin/produit/{id}/modifier', name: 'admin_product_edit')]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    
                }

                $product->setImage($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('admin/produit/{id}/supprimer', name: 'admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();

            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('admin/category/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie ajoutée.');
            return $this->redirectToRoute('admin_category_index');
        }
        return $this->render('admin/category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('admin/category/edit/{id}', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie modifiée.');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('admin/category/delete/{id}', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée.');
        }

        return $this->redirectToRoute('admin_category_index');
    }

    #[Route('/admin/categories', name: 'admin_category_index')]
    public function categoryIndex(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

  
    #[Route('/admin/avis', name: 'admin_avis_index')]
    public function index(DocumentManager $dm): Response
    {
        $avisList = $dm->getRepository(Avis::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/avis/index.html.twig', [
            'avisList' => $avisList,
        ]);
    }

    #[Route('/admin/avis/moderate/{id}', name: 'admin_avis_moderate')]
    public function moderate(string $id, DocumentManager $dm): Response
    {
        $avis = $dm->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException('Avis introuvable');
        }

        $avis->setIsModerated(true);
        $dm->flush();

        $this->addFlash('success', 'Avis modéré avec succès');
        return $this->redirectToRoute('admin_avis_index');
    }
    
    #[Route('/admin/avis/delete/{id}', name: 'admin_avis_delete', methods: ['POST'])]
    public function delete(string $id, DocumentManager $dm, Request $request): Response
    {
        $avis = $dm->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException('Avis introuvable.');
        }

        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete-avis' . $avis->getId(), $submittedToken)) {
            $dm->remove($avis);
            $dm->flush();

            $this->addFlash('success', 'Avis supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_avis_index');
    }

    #[Route('/admin/user', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function showUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response {
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe a été défini
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if (!$this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users');
        }

        if (count($user->getOrders()) > 0) {
            $user->anonymize();
            $this->addFlash('success', 'Utilisateur anonymisé car des commandes sont associées.');
        } else {
            $em->remove($user);
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        $em->flush();

        return $this->redirectToRoute('admin_users');
    }



}

<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Contact;

use App\Form\ProductType;
use App\Form\CategoryForm;
use App\Form\ContactType;
use App\Form\RegistrationForm;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository; 

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormError;



class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $query = $request->query->get('q');
        $isSearch = false;
        $searchResults = [];

        if ($query) {
            $isSearch = true;
            $searchResults = $productRepository->searchByKeyword($query);
        }

        $randomProducts = !$isSearch ? $productRepository->findAllRandom(8) : [];

        return $this->render('home/index.html.twig', [
            'isSearch' => $isSearch,
            'searchResults' => $searchResults,
            'randomProducts' => $randomProducts,
        ]);
    }

    #[Route('/products', name: 'products_index')]
    public function products(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAllWithProducts();

        return $this->render('product.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show')]
    public function productDetail(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/snacks', name: 'category_snacks')]
    public function showSnacks(CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['category' => 'Snacks']);
       
        if (!$category || $category->getProducts()->isEmpty()) {
            $this->addFlash('warning', 'Il n\'y a pas de produits dans la catégorie Snacks.');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('snacks.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/douceurs', name: 'category_douceurs')]
    public function showDouceurs(CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['category' => 'Douceurs']);
        if (!$category || $category->getProducts()->isEmpty()) {
            $this->addFlash('warning', 'Il n\'y a pas de produits dans la catégorie Douceurs.');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('douceurs.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/packs-coffrets', name: 'category_packs')]
    public function showPacks(CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['category' => 'Packs & Coffrets']);
        if (!$category || $category->getProducts()->isEmpty()) {
           $this->addFlash('warning', 'Il n\'y a pas de produits dans la catégorie Packs & Coffrets.');
           return $this->redirectToRoute('app_home');
        }
        return $this->render('packs_coffrets.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('category/{id}', name: 'app_category_show', methods: ['GET'])]
    public function showCategory(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    } 

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             // Le CSRF est validé automatiquement ici
            $contact->setCreatedAt(new \DateTimeImmutable());
            $em->persist($contact);
            $em->flush();

            $email = (new Email())
                ->from('no-reply@leila-snacks.fr') //ne pas tomber en spam
                ->replyTo($contact->getEmail())  
                ->to('taizceccon@hotmail.fr')
                ->subject($contact->getSujet())
                ->text($contact->getMessage());

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Message envoyé avec succès !');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l’envoi du message.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('faq.html.twig');
    }
   
      #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentions(): Response {
        return $this->render('mentions_legales.html.twig');
    }

    #[Route('/conditions-generales', name: 'cgv')]
    public function cgv(): Response {
        return $this->render('cgv.html.twig');
    }

    #[Route('/test-mail', name: 'test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('no-reply@example.com')
            ->to('test@example.com')
            ->subject('Test Mailpit')
            ->text('Ceci est un test !');

        $mailer->send($email);

        return new Response('Email envoyé !');
}

}

    // #[Route('/error', name: 'app_error')]
    // public function error(Request $request): Response
    // {
    //     $exception = $request->attributes->get('exception');

    //     // Exemple de traitement de type d’erreur
    //     if ($exception instanceof NotFoundHttpException) {
    //         return $this->render('error/404.html.twig');
    //     }

    //     return $this->render('error/error.html.twig');
    // }

     // #[Route('/mail-test', name: 'mail_test')]
    // public function sendTestMail(MailerInterface $mailer)
    // {
    //    $email = (new Email())
    //         ->from('hello@leilasd.fr')
    //         ->to('someone@leilasd.fr')
    //         ->subject('Test Mailpit')
    //         ->text('Ceci est un test')
    //         ->html('<p>Ceci est un <strong>test</strong> Mailpit.</p>');

    //     try {
    //         $mailer->send($email);
    //         return $this->json(['status' => 'Email envoyé avec succès !']);
    //     } catch (TransportExceptionInterface $e) {
    //         return $this->json([
    //             'status' => 'Erreur lors de l\'envoi de l\'email',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
         

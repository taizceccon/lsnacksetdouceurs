<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartController extends AbstractController
{
    private function formatPrice(int $amount): string
    {
        return number_format($amount / 100, 2, ',', ' ');
    }

    private function getCartItemCount(array $cart): int
    {
        return array_sum($cart);
    }
    

    #[Route('/cart', name: 'cart_show')]
    public function showCart(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $products = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrix() * $quantity,
                ];
                $total += $product->getPrix() * $quantity;
            }
        }

        return $this->render('cart/show.html.twig', [
            'products' => $products,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function addCart(Request $request, SessionInterface $session): Response
    {
        $productId = $request->request->get('product_id');
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $cart = $session->get('cart', []);
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;

        $session->set('cart', $cart);

        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(Request $request, SessionInterface $session, ProductRepository $productRepository, int $id): JsonResponse
    {
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cart = $session->get('cart', []);

        if (!isset($cart[$id])) {
            return new JsonResponse(['success' => false, 'message' => 'Produit introuvable.'], 404);
        }

        $cart[$id] = $quantity;
        $session->set('cart', $cart);

        $product = $productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['success' => false, 'message' => 'Produit non trouvé en base.'], 404);
        }

        $subtotal = $product->getPrix() * $quantity;
        $total = 0;
        foreach ($cart as $productId => $qty) {
            $p = $productRepository->find($productId);
            if ($p) {
                $total += $p->getPrix() * $qty;
            }
        }

        return new JsonResponse([
            'success' => true,
            'quantity' => $quantity,
            'subtotal' => $this->formatPrice($subtotal),
            'total' => $this->formatPrice($total),
            'cartItemCount' => $this->getCartItemCount($cart)
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeCart(int $id, SessionInterface $session, ProductRepository $productRepository): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);

            $total = 0;
            foreach ($cart as $productId => $qty) {
                $product = $productRepository->find($productId);
                if ($product) {
                    $total += $product->getPrix() * $qty;
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Produit supprimé du panier.',
                'total' => $this->formatPrice($total),
                'cartItemCount' => $this->getCartItemCount($cart)
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Produit non trouvé dans le panier.'
        ], 404);
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clearCart(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/commander', name: 'order_create')]
    public function createOrder(SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur invalide.');
            return $this->redirectToRoute('cart_show');
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_show');
        }

        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $order->setStatus('en vérification');

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if (!$product) {
                $this->addFlash('error', "Produit ID $productId introuvable.");
                return $this->redirectToRoute('cart_show');
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setUnitPrice($product->getPrix());

            $order->addItem($orderItem);
        }

        $em->persist($order);
        $em->flush();

        $session->remove('cart');

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/orders', name: 'order_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $orders = $orderRepository->findBy(['user' => $user]);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/{id}', name: 'order_show')]
    public function show(Order $order): Response
    {
        if ($this->getUser() !== $order->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/orders/all', name: 'order_list')]
    public function list(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();

        return $this->render('order/list.html.twig', [
            'orders' => $orders,
        ]);
    }
}
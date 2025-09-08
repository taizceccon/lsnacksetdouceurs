<?php

namespace App\DataFixtures;


use App\Entity\User;
use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $passwordHasher;

     // Injecte le service de hashage
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {   
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {      
        //Création de l'utilisateur normal
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setNom('User');
        $user->setAdresse('2O Rue du Professeur Roux, 94350 Villiers sur Marne');
        $user->setTelephone('0652192336');
        $hashedPassword = $this->passwordHasher->hashPassword($user, '123456');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $manager->persist($user);

        //Création de l'utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setNom('Leila');
        $admin->setAdresse('27 rue Henri barbusse 93410 - Vaujours');
        $admin->setTelephone('0665448725');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '123456'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $manager->persist($admin);

       // Création des catégories
        $categoriesData = [
            ['category' => 'Snacks'],
            ['category' => 'Douceurs'],
            ['category' => 'Packs & Coffrets']
        ];

        $categories = [];

        foreach ($categoriesData as $data) {
            $category = new Category();
            $category->setCategory($data['category']);
            $manager->persist($category);
            $categories[] = $category;  // Conserver une référence à chaque catégorie pour les associer plus tard
        }

        // Création des produits
        $products = [
            [
                'titre' => 'Coxinhas (Croquettes au poulet)',
                'description' => 'De tendres bouchées brésiliennes au cœur savoureux de poulet effiloché...',
                'prix' => 250,
                'image' => 'coxinha-frango.webp',
                'urlvideo' => NULL,
                'category' => $categories[0]  // Associé à la première catégorie
            ],
            [
                'titre' => 'Rissóis de Viande',
                'description' => 'Croustillants à l’extérieur, fondants à l’intérieur, viande haché... ',
                'prix' => 150,
                'image' => 'rissois-viande.webp',
                'urlvideo' => NULL,
                'category' => $categories[0]  // Associé à la première catégorie
            ],
            [
                'titre' => 'Boulettes de fromage',
                'description' => 'De petites boules dorées et croustillantes remplies de fromage fondant...',
                'prix' => 120,
                'image' => 'bolinhas.webp',
                'urlvideo' => NULL,
                'category' => $categories[0]  // Associé à la première catégorie
            ],
             [
                'titre' => 'Accras de Morue',
                'description' => 'Découvrez le Bolinho de Bacalhau, une délicieuse boulette de morue croustillante à l’extérieur et fondante à l’intérieur.',
                'prix' => 160,
                'image' => 'bolinho.webp',
                'urlvideo' => NULL,
                'category' => $categories[0]  // Associé à la première catégorie
            ],
            [
                'titre' => 'Brigadeiro',
                'description' => 'De petites bouchées fondantes au chocolat, roulées dans des vermicelles croquants...',
                'prix' => 180,
                'image' => 'brigadeiro.webp',
                'urlvideo' => NULL,
                'category' => $categories[1]  // Associé à la deuxième catégorie
            ],
            [
                'titre' => 'Douceur au Coco - Beijinho',
                'description' => 'Une bouchée tendre et sucrée à base de lait concentré et de noix de coco...',
                'prix' => 185,
                'image' => 'douceurcoco.webp',
                'urlvideo' => NULL,
                'category' => $categories[1]  // Associé à la deuxième catégorie
            ],
            [
                'titre' => 'Gâteau dans le pot',
                'description' => 'Des couches moelleuses de gâteau, alternant avec des crèmes onctueuses et des garnitures savoureuses, le tout présenté dans un pot pratique et prêt à déguster. Une explosion de saveurs à chaque cuillère !',
                'prix' => 400,
                'image' => 'bolo-no-pote.webp',
                'urlvideo' => NULL,
                'category' => $categories[1]  // Associé à la troisième catégorie
            ],
            [
                'titre' => 'Box 100 Mini Snacks',
                'description' => 'Box - 25 Mini Hot dogs - 25 quibes - 25 Bolinhas de Queijo - 25 Coxinhas (poulet)',
                'prix' => 6000,
                'image' => 'box.webp',
                'urlvideo' => NULL,
                'category' => $categories[2]  // Associé à la troisième catégorie
            ],
            [
                'titre' => 'Kit Party',
                'description' => 'pour 20 persones25 quibes, - 25 Coxinhas (viande), 25 Bolinhas de Queijo - 25 rissois de viande,  ',
                'prix' => 20000,
                'image' => 'kit.webp',
                'urlvideo' => NULL,
                'category' => $categories[1]  // Associé à la troisième catégorie
            ]

        ];

        // Insérer chaque produit dans la base
        foreach ($products as $productData) {
            $product = new Product();
            $product->setTitre($productData['titre']);
            $product->setDescription($productData['description']);
            $product->setPrix($productData['prix']);
            $product->setImage($productData['image']);
            $product->setUrlvideo($productData['urlvideo']);
            $product->setCategory($productData['category']);
            $manager->persist($product);
        }

        // Enregistrer dans la base
        $manager->flush();
    }
}

 // // Products avec category aleatoire        
        // for ($i = 0; $i< 20; $i++){        
        //     $product = new Product();
        //     $product->setTitre('Product '.$i);
        //     $product->setDescription('Découvrez le produit -  description.'.$i);
        //     $product->setPrix(mt_rand(500, 2500));
        //     $product->setImage("test.webp");  
        //     $product->setUrlvideo("http://www.youtube.com/v=product.$i"); 
            
        //     $randomCategory = $categories[array_rand($categories)];
        //     $product->setCategory($randomCategory);
        
        //     $manager->persist($product);
        //     $manager->flush();
        // } 

        // $product = new Product();
        // $manager->persist($product);
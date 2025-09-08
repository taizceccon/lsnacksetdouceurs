<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    // Indique à PHPUnit quelle classe Kernel utiliser
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful(); // HTTP 200
        $this->assertSelectorTextContains('h1', 'Snacks & Douceurs de Leila');
    }
    public function testProductsPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/products');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1, h2, h3'); 
    }
    public function testAboutAndFaqPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/a-propos');
        $this->assertResponseIsSuccessful();
        $client->request('GET', '/faq');
        $this->assertResponseIsSuccessful();
    }
    public function testMentionsAndCgvPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1, h2'); // titre de la page
        $client->request('GET', '/conditions-generales');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1, h2'); // titre de la page
    }
}
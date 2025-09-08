<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\Product;

class ProductTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $product = new Product();
        $product->setTitre('Chocolat');
        $this->assertEquals('Chocolat', $product->getTitre());
        $product->setDescription('Délicieux chocolat artisanal.');
        $this->assertEquals('Délicieux chocolat artisanal.', $product->getDescription());
        $product->setPrix(250); // prix en centimes ou unité définie
        $this->assertEquals(250, $product->getPrix());
        $product->setImage('chocolat.webp');
        $this->assertEquals('chocolat.webp', $product->getImage());
        $product->setUrlvideo('https://youtube.com/video');
        $this->assertEquals('https://youtube.com/video', $product->getUrlvideo());
    }
    public function testOrderItemsCollection(): void
    {
        $product = new Product();        
        $orderItem = $this->createMock(\App\Entity\OrderItem::class);
        // ajouter un OrderItem
        $product->addOrderItem($orderItem);
        $this->assertCount(1, $product->getOrderItems());
        // retirer un OrderItem
        $product->removeOrderItem($orderItem);
        $this->assertCount(0, $product->getOrderItems());
    }
}
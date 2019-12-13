<?php
namespace App\Controller;

use App\Entity\OrderEntity;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\OrderItemRepository;
use App\Repository\ProductRepository;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RestController extends Controller
{
    public function indexAction()
    {
        return $this->json([
            "name" => "Test Symfony API",
            "version" => "1.0"
        ]);
    }

    public function generateProductsAction()
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        for ($i=1; $i<=10; $i++) {
            $product = new Product();
            $product->setName(uniqid());
            $product->setPrice(rand(1, 100000)/100);
            $em->persist($product);
        }
        $em->flush();
        return $this->json(true);
    }

    public function getProductsAction()
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        return $this->json($em->getRepository('App:Product')->findAll());
    }

    public function createOrderAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $order = new OrderEntity();
        $order->setStatus(OrderEntity::STATUS_NEW);
        $em->persist($order);
        $em->flush();

        // TODO we cat do it in queue
        /** @var ProductRepository $productRepository */
        $productRepository = $em->getRepository('App:Product');
        $itemsData = json_decode($request->getContent(), true);


        foreach ($itemsData as $itemData) {
            $product = $productRepository->find($itemData['id']);
            $orderItem = new OrderItem();
            $orderItem->setOrderId($order->getId());
            $orderItem->setQty($itemData['qty']);
            $orderItem->setProductId($itemData['id']);
            $orderItem->setPrice($product->getPrice());
            $em->persist($orderItem);
        }
        $em->flush();

        return $this->json($order->getId());
    }

    public function payOrderAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var OrderEntity $order */
        $order = $em->getRepository('App:OrderEntity')->find($data['orderId']);

        if (!$order) {
            throw new HttpException(404);
        }

        if ($order->getStatus() === OrderEntity::STATUS_PAID) {
            return $this->json(false);
        }

        /** @var OrderItemRepository $orderItemRepository */
        $orderItemRepository = $em->getRepository('App:OrderItem');
        if ((int)($data['sum']*100) !== (int)($orderItemRepository->getOrderSum($data['orderId'])*100)) {
            return $this->json(false);
        }

        $curl = new Curl();
        $curl->get("https://ya.ru");
        if ($curl->getHttpStatus() !== 200) {
            return $this->json(false);
        }

        $order->setStatus(OrderEntity::STATUS_PAID);
        $em->persist($order);
        $em->flush();

        return $this->json(true);
    }
}
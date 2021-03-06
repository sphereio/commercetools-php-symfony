<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentCollection;
use Commercetools\Core\Request\Payments\Command\PaymentSetAmountPaidAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentManagerTest extends TestCase
{
    /** @var PaymentRepository paymentRepository */
    private $paymentRepository;
    private $eventDispatcher;

    public function setUp()
    {
        $this->paymentRepository = $this->prophesize(PaymentRepository::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testGetPaymentById()
    {
        $this->paymentRepository->getPaymentById('en', '123')
            ->willReturn(Payment::of()->setId('123'))->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getPaymentById('en', '123');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('123', $payment->getId());
    }

    public function testGetPaymentForUser()
    {
        $this->paymentRepository->getPayment('en', '123', null, 'anon-1')
            ->willReturn(Payment::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getPaymentForUser('en', '123', null, 'anon-1');

        $this->assertInstanceOf(Payment::class, $payment);
    }

    public function testGetMultiplePayments()
    {
        $this->paymentRepository->getPaymentsBulk('en', ['payment-1', 'payment-2'])
            ->willReturn(PaymentCollection::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getMultiplePayments('en', ['payment-1', 'payment-2']);

        $this->assertInstanceOf(PaymentCollection::class, $payment);
    }

    public function testCreatePaymentForUser()
    {
        $this->paymentRepository->createPayment('en', Argument::type(Money::class), null, 'anon-1', null, null)
            ->willReturn(Payment::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $manager->createPaymentForUser('en', Money::of(), null, 'anon-1');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreatePaymentWithoutUser()
    {
        $this->paymentRepository->createPayment('en', Money::of())->shouldNotBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $manager->createPaymentForUser('en', Money::of());
    }

    public function testUpdate()
    {
        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $update = $manager->update(Payment::of()->setKey('payment-1'));

        $this->assertInstanceOf(PaymentUpdateBuilder::class, $update);
    }

    public function testApply()
    {
        $payment = Payment::of()->setId('1');

        $this->paymentRepository->update($payment, Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(PaymentPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->apply($payment, []);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('1', $payment->getId());
    }

    public function testDispatch()
    {
        $payment = Payment::of()->setId('1');
        $action = PaymentSetAmountPaidAction::of();

        $this->eventDispatcher->dispatch(
            Argument::type(PaymentUpdateEvent::class),
            Argument::containingString(PaymentSetAmountPaidAction::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatch($payment, $action);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(PaymentSetAmountPaidAction::class, current($actions));
    }

    public function testDispatchPostUpdate()
    {
        $payment = Payment::of()->setId('1');
        $action = PaymentSetAmountPaidAction::of();

        $this->eventDispatcher->dispatch(
            Argument::type(PaymentPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatchPostUpdate($payment, [$action]);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(PaymentSetAmountPaidAction::class, current($actions));
    }
}

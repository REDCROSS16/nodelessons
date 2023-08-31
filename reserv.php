<?php

/**
 * Created by PhpStorm
 * User: Sergey Gruzdov
 * Date: 17.06.23
 * Time: 15:33
 * Project: vds
 */

declare(strict_types=1);

namespace Application\Service\Calculator\Handler;

use Application\Entity\OrderItem;
use Application\Entity\ParameterValue;
use Application\Service\Calculator\CalculatorService;
use Application\Service\Calculator\CalculatorState;
use Application\Service\Calculator\Exception\RuntimeException;
use Application\Service\Calculator\Exception\SkipHandlerException;
use Application\Service\Calculator\Handler\InitialStateHandler\SLShieldsWithMainOrder;
use Application\Service\Calculator\Handler\InitialStateHandler\SLShieldsWithoutMainOrder;
use Application\Service\Calculator\Parameter;
use Application\Service\Calculator\ParameterProvider;
use Application\Service\Calculator\Request;
use Application\Service\Calculator\Value;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Steelline\Common\Tools\DataTransformer as DT;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class MainOrderParamHandler
 *
 * @package Application\Service\Calculator\Handler
 * @author  Sergey Gruzdov <s.gruzdov367@gmail.com>
 */
class MainOrderParamHandler implements HandlerInterface
{
    private const SHIELD_SIDE_OUTSIDE = 'outside';
    private const SHIELD_SIDE_INSIDE = 'inside';

    private const WIDTH_PARAM = 'width';
    private const HEIGHT_PARAM = 'height';

    private const SHIELD_OPEN_SIDE = 'openingSide';
    private const SHIELD_OPEN_SIDE_LEFT = 'left';
    private const SHIELD_OPEN_SIDE_RIGHT = 'right';
    private const FINISHING_INSIDE = 'b46d2ff4-12fa-4943-be57-2aaa5dca7ba6';
    private const FINISHING_OUTSIDE = '4a5f9e01-74ac-42e9-969b-adb1a7d0faff';

    private const COLOR_OUTSIDE = '24df6934-658e-4105-a5fd-8d089d195b92';
    private const COLOR_TYPE_INSIDE = '8976fb71-e982-48d6-ae4f-7efa2b9e53ca';
    private const COLOR_TYPE_OUTSIDE = 'd2757979-2f2a-4b34-9032-9d14e1121a80';

    private const SHIELD_SIDE_OUTSIDE_ID = '9ed36d9a-f69d-11eb-80ee-ac1f6b47e527';
    public const SHIELD_SIDE_INSIDE_ID = '9ed36d9b-f69d-11eb-80ee-ac1f6b47e527';

    private const SHIELD_SIDE_KEYS = [
        self::SHIELD_SIDE_OUTSIDE => 0,
        self::SHIELD_SIDE_INSIDE  => 1,
    ];

    private const SHIELD_SIDE_COMPARISON_KEYS = [
        self::SHIELD_SIDE_OUTSIDE_ID => self::SHIELD_SIDE_OUTSIDE,
        self::SHIELD_SIDE_INSIDE_ID => self::SHIELD_SIDE_INSIDE,
    ];

    private const SHIELD_OPEN_SIDE_COMPARISON_KEYS = [
        self::SHIELD_OPEN_SIDE_LEFT => 'e887eacf-ad1f-11ed-80f6-ac1f6b47e527',
        self::SHIELD_OPEN_SIDE_RIGHT =>'f1c95759-ad1f-11ed-80f6-ac1f6b47e527',
    ];

    private const COMPARISON_TERMS_LIST = [
        '84eab270-63fa-11ed-80f4-ac1f6b47e527' => 1, // щит малый
        '29d7de98-ff1c-11eb-80ee-ac1f6b47e527' => 1, // щит основной
        '8fd5bf0c-63fa-11ed-80f4-ac1f6b47e527' => 1, // добор верх
        'e86b5292-adcc-11ed-80f6-ac1f6b47e527' => 1, // добор левый
        '84eab271-63fa-11ed-80f4-ac1f6b47e527' => 1, // добор правый
        'f2f3cef5-adcc-11ed-80f6-ac1f6b47e527' => 2, // наличник левый
        '389f2762-63fb-11ed-80f4-ac1f6b47e527' => 2, // наличник правый
        '29d7de97-ff1c-11eb-80ee-ac1f6b47e527' => 3, // наличник верхний/портал
        '4dc3c505-63fb-11ed-80f4-ac1f6b47e527' => 4, // нащельник
    ];

    private const FORM_PARAMS_BY_KIND = [
        0 => [], // stub if we get incorrect key from 1C
        1 => [
            'aff8f7c7-c6a0-43af-8af9-41eb15f32ced', // сторона щита
            'cd13fd41-be27-4301-a730-9309df0fc672', // тип
            'eea23d7f-2af5-4343-90bf-2e2b1381dabd', // улица
            '46329986-8910-43f3-9a9e-c79b1e80cb42', // окно
            '63d23d9b-a3c9-442a-a239-06cb519b2fb8', // отделка
            '53769d0b-1e1e-479e-8009-7d8989aac96e', // цвет
            'ce3ccaab-85aa-466d-bc20-13918e9b8fd1', // тип краски
            '49d50f67-8945-4c7a-8665-e531e98821df', // рисунок
            '6df25d78-b8b6-407f-8076-de56f22d5edd', // патина
            '043f8ae9-feb3-498a-91b5-c35b959cf0e1', // вставки
            '8b16ec30-e394-4e29-9366-31997bdbf1ec', // цвет вставки
            'a23550e1-b3ea-4f98-a3c6-50d48b00986c', // рамка
            '8251a324-ae6f-414c-ad29-56d4847e8d1e', // цвет рамки
            '1bd281e2-33e2-4239-827e-143d25d5f40b', // отбойник
            '369ba7de-e727-4d52-95ac-8b83f99e687f', // сторона открывания
            '6251b86a-1653-4ba3-b851-f6f716941829', // ширина
            '3931c54f-e171-11e6-9d41-001e671968b0', // высота
        ],
        2 => [
            'aff8f7c7-c6a0-43af-8af9-41eb15f32ced', // сторона щита
            'cd13fd41-be27-4301-a730-9309df0fc672', // тип
            'eea23d7f-2af5-4343-90bf-2e2b1381dabd', // улица
            '63d23d9b-a3c9-442a-a239-06cb519b2fb8', // отделка
            '53769d0b-1e1e-479e-8009-7d8989aac96e', // цвет
            'ce3ccaab-85aa-466d-bc20-13918e9b8fd1', // тип краски
            '49d50f67-8945-4c7a-8665-e531e98821df', // рисунок
            '6df25d78-b8b6-407f-8076-de56f22d5edd', // патина
            'f517ac39-ba8f-41cb-bd2f-4ffad30ab435', // выборка
            'd78405c0-2b1b-44b5-8b4e-6a3622532e5c', // торцы
            '6251b86a-1653-4ba3-b851-f6f716941829', // ширина
            '3931c54f-e171-11e6-9d41-001e671968b0', // высота
        ],
        3 => [
            'aff8f7c7-c6a0-43af-8af9-41eb15f32ced', // сторона щита
            'cd13fd41-be27-4301-a730-9309df0fc672', // тип
            'eea23d7f-2af5-4343-90bf-2e2b1381dabd', // улица
            '63d23d9b-a3c9-442a-a239-06cb519b2fb8', // отделка
            '53769d0b-1e1e-479e-8009-7d8989aac96e', // цвет
            'ce3ccaab-85aa-466d-bc20-13918e9b8fd1', // тип краски
            '49d50f67-8945-4c7a-8665-e531e98821df', // рисунок
            '6df25d78-b8b6-407f-8076-de56f22d5edd', // патина
            '2ed5754a-e5d3-11e9-80d7-ac1f6b47e527', // крепление на стену
            '6251b86a-1653-4ba3-b851-f6f716941829', // ширина
            '3931c54f-e171-11e6-9d41-001e671968b0', // высота
        ],
        4 => [
            '63d23d9b-a3c9-442a-a239-06cb519b2fb8', // отделка
            'eea23d7f-2af5-4343-90bf-2e2b1381dabd', // улица
            '53769d0b-1e1e-479e-8009-7d8989aac96e', // цвет
            'ce3ccaab-85aa-466d-bc20-13918e9b8fd1', // тип краски
            '6df25d78-b8b6-407f-8076-de56f22d5edd', // патина
            '6251b86a-1653-4ba3-b851-f6f716941829', // ширина
            '3931c54f-e171-11e6-9d41-001e671968b0', // высота
        ],
    ];

    /**
     * Ключ формы => [Ключ формы двери снаружи, Ключ формы двери внутри]
     * or
     * Ключ формы => Ключ формы двери
     */
    private const COMPARISON_FORM_KEYS = [
        '63d23d9b-a3c9-442a-a239-06cb519b2fb8' => [self::FINISHING_OUTSIDE, self::FINISHING_INSIDE], // отделка
        '53769d0b-1e1e-479e-8009-7d8989aac96e' => [self::COLOR_OUTSIDE, '815529a8-0501-4588-bfe8-3a39bb52317a'], // цвет
        'ce3ccaab-85aa-466d-bc20-13918e9b8fd1' => [self::COLOR_TYPE_OUTSIDE, self::COLOR_TYPE_INSIDE], // тип краски
        '49d50f67-8945-4c7a-8665-e531e98821df' => ['305a1626-5975-4dfb-8ff1-4b244f698000', 'dc79a8b8-9bbc-4e3e-9bdc-cfb83b257cc0'], // рисунок
        '6df25d78-b8b6-407f-8076-de56f22d5edd' => ['5b447c40-ee57-4b0d-a269-0c805ce67f30', '278d5002-6486-43c8-94bd-9a6a7c60e7c2'], // патина
        '043f8ae9-feb3-498a-91b5-c35b959cf0e1' => ['043f8ae9-feb3-498a-91b5-c35b959cf0e1', '682f7b50-8bef-4116-9f11-f0c96ca65dcb'], // вставки
        '8b16ec30-e394-4e29-9366-31997bdbf1ec' => ['37fd013a-b458-4d1f-82e0-6f50508ddf14', '8ccfeab1-ee61-4fc2-8c9d-6c84b3ace0f0'], // цвет вставки
        'a23550e1-b3ea-4f98-a3c6-50d48b00986c' => ['a23550e1-b3ea-4f98-a3c6-50d48b00986c', '7825cb60-3c01-4b76-ab40-aa9f5173a435'], // рамка
        '8251a324-ae6f-414c-ad29-56d4847e8d1e' => ['8251a324-ae6f-414c-ad29-56d4847e8d1e', '6cd25a1f-dca5-46c6-b057-8a7d1d5331c1'], // цвет рамки
        '1bd281e2-33e2-4239-827e-143d25d5f40b' => ['131318d3-d06a-40a0-a8b7-9e236bdb3596', '784ade69-247a-4966-adf4-3e332577256d'], // отбойник
        'cd13fd41-be27-4301-a730-9309df0fc672' => 'cd13fd41-be27-4301-a730-9309df0fc672', // тип
        '369ba7de-e727-4d52-95ac-8b83f99e687f' => 'openingSide', // сторона открывания
        '6251b86a-1653-4ba3-b851-f6f716941829' => 'width', // ширина
        '3931c54f-e171-11e6-9d41-001e671968b0' => 'height', // высота
    ];

    private const HAS_NOT_CHANGED_PARAMS = [
        'cd13fd41-be27-4301-a730-9309df0fc672', // тип
        '369ba7de-e727-4d52-95ac-8b83f99e687f', // сторона открывания
        'eea23d7f-2af5-4343-90bf-2e2b1381dabd', // улица
        '46329986-8910-43f3-9a9e-c79b1e80cb42', // окно
        '6251b86a-1653-4ba3-b851-f6f716941829', // ширина
        '3931c54f-e171-11e6-9d41-001e671968b0', // высота
    ];

    public const RESET_VALUE_PARAMS = [
        '6df25d78-b8b6-407f-8076-de56f22d5edd', // Патина
        'ce3ccaab-85aa-466d-bc20-13918e9b8fd1', // Тип краски
        '043f8ae9-feb3-498a-91b5-c35b959cf0e1', // Вставка
    ];

    public const FINISHING_NO_OUTSIDE = [
        '8a43d9a4-34dd-11e4-9daf-001e671968b0', // без щита
        '1338a724-52a7-11e8-b0c3-001e671968b0', // готовый щит
        'f7f74f23-db8d-11e9-80d7-ac1f6b47e527', // МДФ 8 мм Временный щит
        '46daa5d3-ce0a-11e8-a9f5-001e671968b0', // МДФ 12 мм Временный щит
        '2b5d7321-f80e-11e6-9d41-001e671968b0', // МДФ 16 мм Временный щит
        '1344518e-0b08-11e3-95a2-001e671968b0', // Металл
        '7b852bbf-f869-11e4-a366-001e671968b0', // МЕТАЛЛ. ВСТАВКИ НЕРЖАВЕЙКА
        //        'c52c6676-77c0-11e5-9efd-001e671968b0', // Цвет заказчика

        'f9addc70-13e4-11e9-80cd-ac1f6b47e527', // МДФ 8мм временный щит внутри
        '0b0ad62d-8c61-11e5-a814-001e671968b0', // profilDoors
        'b7969d83-a8f3-11e7-866e-001e671968b0', // без щита
        '98747aa3-ed10-11e5-83da-001e671968b0', // готовый щит
        'c732cdba-e8cc-11e4-a1ed-001e671968b0', // Заглушка из ДВП при отсутствии внутреннего щита
        '75f3a989-aa9d-11e8-b4f7-001e671968b0', // временный щит
        '2fa805d4-2331-11e6-a64d-001e671968b0', // МДФ 16 мм
        '8a43d9a3-34dd-11e4-9daf-001e671968b0', // МДФ 16 мм Временный щит
        'f9addc70-13e4-11e9-80cd-ac1f6b47e527', // МДФ 8 мм Временный щит
        '35812405-0b0f-11e3-95a2-001e671968b0', // МЕТАЛЛ
        'b20cbb2e-5781-11e5-9aed-001e671968b0', // Щит заказчика
        'c17b809e-7c69-11e4-8e81-001e101f57d0', // Закладные под молдинги
        '0cbdb69b-eae3-11e7-866e-001e671968b0', // МДФ 10 мм Временный щит
        'cdfbf90a-85ca-11e4-ba9a-001e101f82a7', // МДФ 8 мм сырая (без пленки, без фрезеровки)
        '36ba2740-3cc7-11e5-b72f-001e671968b0', // МДФ 16 мм(вл)
        '6a22e2aa-3338-11e4-9daf-001e671968b0', // Щит заказчика "Катрин" (с косичкой)
        'f88c522e-31cb-11e4-9daf-001e671968b0', // Щит заказчика "Поло" (со штапиками)
    ];

    /**
     * @var bool
     */
    public static $isNewElement = false;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CalculatorState
     */
    private $calculatorState;

    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var HandlerLocator
     */
    private $handlerLocator;

    /**
     * @var Parameter
     */
    private $mainOrderParam;

    /**
     * @var OrderItem
     */
    private $orderItem;

    /**
     * Constructor MainOrderParamHandler
     *
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param Request                $request
     * @param CalculatorState        $calculatorState
     * @param ParameterProvider      $parameterProvider
     * @param HandlerLocator         $handlerLocator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        Request $request,
        CalculatorState $calculatorState,
        ParameterProvider $parameterProvider,
        HandlerLocator $handlerLocator
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->request = $request;
        $this->calculatorState = $calculatorState;
        $this->parameterProvider = $parameterProvider;
        $this->handlerLocator = $handlerLocator;
    }

    /**
     * @param ...$params
     * @return HandlerInterface|MainOrderParamHandler
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function handle(...$params): HandlerInterface
    {
        try {
            $this->initParameter();
            $this->processParameter();
            $this->loadMainOrder();
            $this->resetSubordinateParams();
            $this->checkChangeKindParam();
            $this->processSubordinateParams();
            $this->clearForceLockedValuesInItems();
        } catch (SkipHandlerException $ex) {
            // do nothing
        } finally {
            $this->saveParameter();
        }

        return $this;
    }

    /**
     * @return void
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function initParameter(): void
    {
        if (!$this->calculatorState->getParams()->get(SLShieldsWithMainOrder::MAIN_ORDER_PARAM)) {
            $this->handlerLocator->getConditionsHandler()->handle(SLShieldsWithMainOrder::MAIN_ORDER_PARAM);
            $this->parameterProvider->get(SLShieldsWithMainOrder::MAIN_ORDER_PARAM, 'Номер основного заказа')
                ->setType(Parameter::TYPE_INPUT)
                ->setRequired();
        }


        if (self::$isNewElement) {
            $mainOrderPrevParam = $this->getMainOrderParamPrevPosition();

            if ($mainOrderPrevParam !== null) {
                $this->parameterProvider->get(SLShieldsWithMainOrder::MAIN_ORDER_PARAM)
                    ->setValue(new Value($mainOrderPrevParam->getValue()->getId(), $mainOrderPrevParam->getValue()->getId(), null, [Value::FORCE_LOCK]))
                    ->disable()
                    ->setForceLocked()
                    ->setChanged();
            }
        }

        $this->mainOrderParam = $this->parameterProvider->get(SLShieldsWithMainOrder::MAIN_ORDER_PARAM);
    }

    /**
     * @return void
     * @throws SkipHandlerException
     */
    private function processParameter(): void
    {
        if ($this->request->getParamId() !== SLShieldsWithMainOrder::MAIN_ORDER_PARAM) {
            return;
        }

        if (!$this->mainOrderParam->hasValue()) {
            throw new SkipHandlerException();
        }

        $this->mainOrderParam->disable()->setForceLocked()->setChanged();
    }

    /**
     * @return void
     * @throws SkipHandlerException
     */
    private function loadMainOrder(): void
    {
        if (!$this->mainOrderParam->hasValue()) {
            throw new SkipHandlerException();
        }

        $orderItem = $this->entityManager->getRepository(OrderItem::class)->getOrderItemByCode(DT::toStringOrNull($this->mainOrderParam->getValue()->getId()));

        if ($orderItem === null) {
            throw new RuntimeException($this->translator->trans('error.load_main_order'));
        }

        $productType = $orderItem->getData()['productType'] ?? null;

        if ($productType !== CalculatorService::METAL_DOOR_FORM_ALIAS) {
            throw new RuntimeException($this->translator->trans('error.incorrect_main_order'));
        }

        $params = $orderItem->getData()['params'];

        if ($this->checkShieldFinishingAndSetAvailableSide($params)) {
            throw new RuntimeException($this->translator->trans('error.incorrect_main_order'));
        }

        $this->orderItem = $orderItem;
    }

    /**
     * @return void
     */
    private function resetSubordinateParams(): void
    {
        if (!($this->request->getParamId() === SLShieldsWithoutMainOrder::KIND && $this->request->getValueId() === null)) {
            return;
        }

        foreach ($this->calculatorState->getParams() as $param) {
            if (\in_array($param->getId(), [SLShieldsWithMainOrder::MAIN_ORDER_PARAM, SLShieldsWithoutMainOrder::COUNT, SLShieldsWithoutMainOrder::KIND])) {
                continue;
            }

            $param->resetValue(true)->resetItems(true)->setChanged();

            if (\in_array($param->getId(), self::HAS_NOT_CHANGED_PARAMS)) {
                $param->enable()->forceUnlock();
            }

            $this->calculatorState->updateParam($param);
        }
    }

    /**
     * @return void
     */
    private function checkChangeKindParam(): void
    {
        if (!($this->request->getParamId() === SLShieldsWithoutMainOrder::KIND && $this->request->getValueId() !== null)) {
            return;
        }

        $kindParam = $this->parameterProvider->get(SLShieldsWithoutMainOrder::KIND);

        foreach (self::FORM_PARAMS_BY_KIND[self::COMPARISON_TERMS_LIST[$kindParam->getPrevValue()->getId()] ?? 0] ?? [] as $paramId) {
            $param = $this->parameterProvider->get($paramId)
                ->resetValue(true)
                ->resetItems(true)
                ->setChanged();

            if (\in_array($param->getId(), self::HAS_NOT_CHANGED_PARAMS)) {
                $param->enable()->forceUnlock();
            }

            $this->calculatorState->updateParam($param);
        }
    }

    /**
     * @return void
     * @throws SkipHandlerException
     */
    private function processSubordinateParams(): void
    {
        if (!\in_array($this->request->getParamId(), [SLShieldsWithMainOrder::MAIN_ORDER_PARAM, SLShieldsWithoutMainOrder::KIND, SLShieldsWithoutMainOrder::SHIELD_SIDE])) {
            return;
        }

        $kindParam = $this->parameterProvider->get(SLShieldsWithoutMainOrder::KIND);
        $shieldSideParam = $this->parameterProvider->get(SLShieldsWithoutMainOrder::SHIELD_SIDE);

        if (!$this->mainOrderParam->hasValue() || !$kindParam->hasValue()) {
            throw new SkipHandlerException();
        }

        $params = $this->orderItem->getData()['params'] ?? [];

        if ($shieldSideParam->getValue()->getId() === null) {
            $this->tryToLoadShieldSide($params);
        }

//        if ($this->availableSide === self::SHIELD_SIDE_OUTSIDE_ID || $this->availableSide === self::SHIELD_SIDE_INSIDE_ID) {
//            $this->forceLoadShieldSide($this->availableSide);
//        }

        $shieldSide = self::SHIELD_SIDE_COMPARISON_KEYS[$shieldSideParam->getValue()->getId()] ?? self::SHIELD_SIDE_OUTSIDE;
        $shieldSideKey = self::SHIELD_SIDE_KEYS[$shieldSide];

        $openSide = $params[self::SHIELD_OPEN_SIDE]['value'] ?? null;
        $openSideKey = $openSide ? self::SHIELD_OPEN_SIDE_COMPARISON_KEYS[$openSide['id']] : null;
        $openSideLabel = $openSide ? $openSide['label'] : null;

        $this->resetParamsBeforeSetNewValue();

        foreach (self::FORM_PARAMS_BY_KIND[self::COMPARISON_TERMS_LIST[$kindParam->getValue()->getId()] ?? 0] ?? [] as $paramId) {
            $paramKey = self::COMPARISON_FORM_KEYS[$paramId] ?? $paramId;

            if (\is_array($paramKey)) {
                $paramKey = $paramKey[$shieldSideKey];
            }

            $doorParam = $params[$paramKey] ?? null;

            if ($doorParam === null || ($doorParam['value'] ?? null) === null) {
                continue;
            }

            switch ($doorParam['id']) {
                case self::WIDTH_PARAM:
                case self::HEIGHT_PARAM:
                    $shieldParamValue = $doorParam['value'];
                    break;
                case self::SHIELD_OPEN_SIDE:
                    $shieldParamValue =  new Value($openSideKey, $openSideLabel, $openSideLabel, [Value::FORCE_LOCK]);
                    break;
                case self::FINISHING_INSIDE:
                case self::FINISHING_OUTSIDE:
                case self::COLOR_OUTSIDE:
                    $shieldParamValue = $this->getShieldParamValue(DT::toString($doorParam['value']['id'])) ?? $doorParam['value'];
                    break;
                case self::COLOR_TYPE_INSIDE:
                case self::COLOR_TYPE_OUTSIDE:
                    $shieldParamValue = $doorParam['value']['id'] === null
                        ? $shieldParamValue = null
                        : $this->getShieldParamValue(DT::toString($doorParam['value']['id'])) ?? $doorParam['value'];
                    break;
                default:
                    $shieldParamValue = $doorParam['value']['id'] !== null ? $this->getShieldParamValue(DT::toString($doorParam['value']['id'])) : null;
            }

            if ($shieldParamValue === null) {
                continue;
            }

            $id = ($shieldParamValue instanceof ParameterValue) ? $shieldParamValue->getExternalId() : $doorParam['value']['id'];
            $label = ($shieldParamValue instanceof ParameterValue) ? $shieldParamValue->getLabel() : $doorParam['value']['label'];

            $value = new Value($id, $label, $label, [Value::FORCE_LOCK]);

            $this->updateParam($paramId, $value);
        }
    }

    /**
     * @return void
     */
    private function clearForceLockedValuesInItems(): void
    {
        foreach ($this->calculatorState->getParams() as $param) {
            $selectedValue = $param->getValue();

            if ($selectedValue->hasFlag(Value::FORCE_LOCK)) {
                $param->setItems($param->getItems()->filter(function (Value $value) use ($selectedValue) {
                    return !$value->hasFlag(Value::FORCE_LOCK) || $value->getId() === $selectedValue->getId();
                }));
            }
        }
    }

    /**
     * @return void
     */
    private function saveParameter(): void
    {
        if ($this->mainOrderParam !== null) {
            $this->calculatorState->updateParam($this->mainOrderParam);
        }
    }

    /**
     * @return Parameter|null
     */
    private function getMainOrderParamPrevPosition(): ?Parameter
    {
        $params = $this->calculatorState->getTab()->getTableParamsByKey(SLShieldsWithMainOrder::MAIN_ORDER_PARAM);

        /** @var Parameter $param */
        foreach ($params as $param) {
            if ($param->hasValue()) {
                return $param;
            }
        }

        return null;
    }

    /**
     * @param string $paramId
     * @param Value $value
     * @return void
     */
    private function updateParam(string $paramId, Value $value): void
    {
        $param = $this->parameterProvider->get($paramId);
        $param->setValue($value)->addItem($value)->setChanged();

        if (\in_array($paramId, self::HAS_NOT_CHANGED_PARAMS)) {
            $param->setForceLocked()->disable();
        }

        $this->calculatorState->updateParam($param);
    }

    /**
     * @param string $doorParamExternalId
     * @return ParameterValue
     */
    private function getShieldParamValue(string $doorParamExternalId): ?ParameterValue
    {
        return $this->entityManager
            ->getRepository(ParameterValue::class)
            ->getShieldComparisonValue(DT::toString($doorParamExternalId));
    }

    /**
     * @return void
     */
    private function resetParamsBeforeSetNewValue(): void
    {
        foreach (self::RESET_VALUE_PARAMS as $paramId) {
            $this->parameterProvider->get($paramId)->resetValue(true)->resetItems(true);
        }
    }

    /**
     * @param array $params
     * @return void
     */
    private function tryToLoadShieldSide(array $params): void
    {
        if (in_array($params[self::FINISHING_OUTSIDE]['value']['id'], self::FINISHING_NO_OUTSIDE)) {
            $shieldSideParam = $this->parameterProvider->get(SLShieldsWithoutMainOrder::SHIELD_SIDE);
            $value = $this->entityManager->getRepository(ParameterValue::class)->findOneBy(['externalId' => self::SHIELD_SIDE_INSIDE_ID]);
            $shieldSideParam->setValue((new Value($value->getExternalId(), $value->getLabel(), $value->getLabel(), [Value::FORCE_LOCK])));

            $this->calculatorState->updateParam($shieldSideParam);
        }
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkShieldFinishingAndSetAvailableSide(array $params): bool
    {
        if (in_array($params[self::FINISHING_OUTSIDE]['value']['id'], self::FINISHING_NO_OUTSIDE)
            && in_array($params[self::FINISHING_INSIDE]['value']['id'], self::FINISHING_NO_OUTSIDE)
        ) {
            return true;
        }

        if (in_array($params[self::FINISHING_OUTSIDE]['value']['id'], self::FINISHING_NO_OUTSIDE)) {
            $this->availableSide = self::SHIELD_SIDE_INSIDE_ID;
        }


        if (in_array($params[self::FINISHING_INSIDE]['value']['id'], self::FINISHING_NO_OUTSIDE)) {
            $this->availableSide = self::SHIELD_SIDE_OUTSIDE_ID;
        }

        return false;
    }

    /**
     * @param string $sideId
     * @return void
     */
    private function forceLoadShieldSide(string $sideId): void
    {
        $shieldSideParam = $this->parameterProvider->get(SLShieldsWithoutMainOrder::SHIELD_SIDE);
        $value = $this->entityManager->getRepository(ParameterValue::class)->findOneBy(['externalId' => $sideId]);
        $shieldSideParam->setValue((new Value($value->getExternalId(), $value->getLabel(), $value->getLabel())));
        $shieldSideParam->setDisabled();

        $this->calculatorState->updateParam($shieldSideParam);
    }
}

<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\NewsSource;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class NewsSourceAdmin extends AbstractAdmin
{
    #[\Override]
    public function toString(object $object): string
    {
        return $object instanceof NewsSource && $object->getName()
            ? (string) $object->getName()
            : 'Новый источник';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, [
                'label' => 'Название источника',
                'required' => true,
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL RSS ленты',
                'required' => true,
                'help' => 'Введите полный URL RSS ленты',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Активен',
                'required' => false,
                'help' => 'Только активные источники будут парситься',
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name', null, ['label' => 'Название'])
            ->add('isActive', null, ['label' => 'Активен'])
            ->add('createdAt', null, ['label' => 'Дата создания']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name', null, ['label' => 'Название'])
            ->add('url', null, ['label' => 'URL'])
            ->add('isActive', 'boolean', [
                'label' => 'Активен',
                'editable' => true,
            ])
            ->add('lastParsedAt', 'datetime', [
                'label' => 'Последний парсинг',
                'format' => 'd.m.Y H:i',
            ])
            ->add('createdAt', 'datetime', [
                'label' => 'Создан',
                'format' => 'd.m.Y H:i',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                    'parse' => [
                        'template' => 'admin/news_source/action_parse.html.twig',
                    ],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('name', null, ['label' => 'Название'])
            ->add('url', null, ['label' => 'URL'])
            ->add('description', null, ['label' => 'Описание'])
            ->add('isActive', 'boolean', ['label' => 'Активен'])
            ->add('createdAt', 'datetime', [
                'label' => 'Создан',
                'format' => 'd.m.Y H:i:s',
            ])
            ->add('lastParsedAt', 'datetime', [
                'label' => 'Последний парсинг',
                'format' => 'd.m.Y H:i:s',
            ])
            ->add('updatedAt', 'datetime', [
                'label' => 'Обновлен',
                'format' => 'd.m.Y H:i:s',
            ]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('parse', $this->getRouterIdParameter().'/parse');
    }
}

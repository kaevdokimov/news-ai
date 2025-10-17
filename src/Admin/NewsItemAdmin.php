<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\NewsItem;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class NewsItemAdmin extends AbstractAdmin
{
    public function toString(object $object): string
    {
        return $object instanceof NewsItem && $object->getTitle()
            ? (string) $object->getTitle()
            : 'Новая новость';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('source', null, [
                'label' => 'Источник',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Содержание',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('link', UrlType::class, [
                'label' => 'Ссылка',
                'required' => false,
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'URL изображения',
                'required' => false,
            ])
            ->add('guid', TextType::class, [
                'label' => 'GUID',
                'required' => true,
                'help' => 'Уникальный идентификатор новости',
            ])
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Дата публикации',
                'required' => true,
                'widget' => 'single_text',
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('source', null, ['label' => 'Источник'])
            ->add('title', null, ['label' => 'Заголовок'])
            ->add('publishedAt', null, ['label' => 'Дата публикации'])
            ->add('createdAt', null, ['label' => 'Дата создания']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, [
                'label' => 'Заголовок',
                'template' => 'admin/news_item/list_title.html.twig',
            ])
            ->add('source', null, ['label' => 'Источник'])
            ->add('publishedAt', 'datetime', [
                'label' => 'Дата публикации',
                'format' => 'd.m.Y H:i',
            ])
            ->add('createdAt', 'datetime', [
                'label' => 'Создана',
                'format' => 'd.m.Y H:i',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('source', null, ['label' => 'Источник'])
            ->add('title', null, ['label' => 'Заголовок'])
            ->add('description', null, [
                'label' => 'Описание',
                'template' => 'admin/news_item/show_description.html.twig',
            ])
            ->add('content', null, [
                'label' => 'Содержание',
                'template' => 'admin/news_item/show_content.html.twig',
            ])
            ->add('link', 'url', ['label' => 'Ссылка'])
            ->add('imageUrl', 'url', ['label' => 'URL изображения'])
            ->add('guid', null, ['label' => 'GUID'])
            ->add('publishedAt', 'datetime', [
                'label' => 'Дата публикации',
                'format' => 'd.m.Y H:i:s',
            ])
            ->add('createdAt', 'datetime', [
                'label' => 'Создана',
                'format' => 'd.m.Y H:i:s',
            ])
            ->add('updatedAt', 'datetime', [
                'label' => 'Обновлена',
                'format' => 'd.m.Y H:i:s',
            ]);
    }
}

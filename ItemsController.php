<?php

namespace Rebeam\AdminBundle\Controller;

use Rebeam\AdminBundle\Form\Type\EbayListingType;
use Rebeam\AdminBundle\Form\Type\ItemType;
use Rebeam\AdminBundle\Form\Type\ItemcommentType;
use Rebeam\AppBundle\Model\BrandQuery;
use Rebeam\AppBundle\Model\DefectQuery;
use Rebeam\AppBundle\Model\EbayListing;
use Rebeam\AppBundle\Model\Item;
use Rebeam\AppBundle\Model\ItemImage;
use Rebeam\AppBundle\Model\ItemImageQuery;
use Rebeam\AppBundle\Model\ItemQuery;
use Rebeam\AppBundle\Model\Itemcomment;
use Rebeam\AppBundle\Model\ItemcommentQuery;
use Rebeam\AppBundle\Model\Listing;
use Rebeam\AppBundle\Model\ModelImageQuery;
use Rebeam\AppBundle\Model\ModelQuery;
use Rebeam\AppBundle\Model\TagQuery;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ItemsController
 *
 * @package Rebeam\AdminBundle\Controller
 */
class ItemsController extends Controller
{
    /**
     * @Route("/items")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $tags = TagQuery::create()
            ->filterById($request->get('tags'), \Criteria::IN)
            ->find();

        $current_filter                 = array();
        $current_filter['brand']        = $request->get('brand', 'alle');
        $current_filter['stock']        = $request->get('stock', 'alle');
        $current_filter['shelf']        = $request->get('shelf', 'alle');
        $current_filter['compartment']  = $request->get('compartment', 'alle');
        $current_filter['show_in_shop'] = $request->get('show_in_shop', 'alle');
        $current_filter['has_listing']  = $request->get('has_listing', 'alle');
        $current_filter['lamp_missing'] = $request->get('lamp_missing', 'alle');
        $current_filter['selling_from'] = $request->get('selling_from', '0000-00-00');
        $current_filter['selling_to']   = $request->get('selling_to', '0000-00-00');
        $current_filter['buying_from']  = $request->get('buying_from', '0000-00-00');
        $current_filter['buying_to']    = $request->get('buying_to', '0000-00-00');

        $filters                = array();
        $filters['brand']       = BrandQuery::create()->distinct()->find();
        $filters['stock']       = $this->getFilterFor($tags, 'item.stock');
        $filters['shelf']       = $this->getFilterFor($tags, 'item.shelf');
        $filters['compartment'] = $this->getFilterFor($tags, 'item.compartment');


        $cols = null;
        parse_str(urldecode($request->get('cols')));
        $selectedCols = $cols;
        if ($selectedCols == null) {
            $cookies = $this->getRequest()->cookies->all();

            if (isset($cookies['selectedCols'])) {
                $selectedCols = unserialize($cookies['selectedCols']);
            }
            $cols_def     = array(
                'id'               => 1,
                'model_id'         => 1,
                'stock'            => 1,
                'shelf'            => 1,
                'compartment'      => 1,
                'serial_number'    => 1,
                'Model.id'         => 1,
                'Model.name'       => 1,
                'Brand.id'         => 1,
                'Brand.name'       => 1,
                'holding_time_es'  => 0,
                'holding_time_sv'  => 0,
                'holding_time_ev'  => 0,
                'show_in_shop'     => 0,
                'has_listing'      => 0,
                'selling_date'     => 0,
                'buying_date'      => 0,
                'lamp_missing'     => 0,
                'selling_price'    => 0,
                'buying_price'     => 0,
                'lamp_hours'       => 0,
                'Model.brightness' => 0,
                'Model.contrast'   => 0,
                'Model.resolution' => 0
            );
            $selectedCols = array_merge($cols_def, (array)$selectedCols);
        } else {
            $cols_def     = array(
                'id'               => 0,
                'model_id'         => 0,
                'stock'            => 0,
                'shelf'            => 0,
                'compartment'      => 0,
                'serial_number'    => 0,
                'Model.id'         => 0,
                'Model.name'       => 0,
                'Brand.id'         => 0,
                'Brand.name'       => 0,
                'holding_time_es'  => 0,
                'holding_time_sv'  => 0,
                'holding_time_ev'  => 0,
                'show_in_shop'     => 0,
                'has_listing'      => 0,
                'selling_date'     => 0,
                'buying_date'      => 0,
                'lamp_missing'     => 0,
                'selling_price'    => 0,
                'buying_price'     => 0,
                'lamp_hours'       => 0,
                'Model.brightness' => 0,
                'Model.contrast'   => 0,
                'Model.resolution' => 0
            );
            $selectedCols = array_merge($cols_def, $selectedCols);
        }


        $text  = $request->get('text');
        $order = $request->get('order', 'id:desc');

        $mode = $request->get('mode');
        if ($mode == 'download-csv') {
            return $this->getFilteredCSV($tags, $current_filter, $text, $selectedCols, $order);
        }

        $response = new Response('', 200, array('content-type' => 'text/html'));
        $response->headers->setCookie(new Cookie('selectedCols', serialize($selectedCols), strtotime('+30 days')));
        $response->send();


        $pagemode = $request->get('p');
        if ($request->isXmlHttpRequest() or $pagemode == 'ajax') {
            $page       = $request->get('page');
            $maxPerPage = $request->get('prepage');

            return $this->render(
                'RebeamAdminBundle:Items:_items.html.twig',
                array(
                    'items'          => $this->getFilteredItems($tags, $current_filter, $page, $maxPerPage, $text, $selectedCols, $order),
                    'filters'        => $filters,
                    'current_filter' => $current_filter,
                    'selectedCols'   => $selectedCols,
                    'orderBy'        => $order
                )
            );
        }


        $all_tags = TagQuery::create()->orderByName()->find();

        return (array(
            'items'          => $this->getFilteredItems($tags, $current_filter, 1, 25, ''),
            'allTags'        => $all_tags,
            'selectedTags'   => $request->get('tags'),
            'selectedCols'   => $selectedCols,
            'filters'        => $filters,
            'current_filter' => $current_filter,
            'orderBy'        => $order

        ));
    }

    /**
     * @param        $tags
     * @param        $current_filter
     * @param        $text
     * @param        $selectedCols
     * @param string $order
     *
     * @return StreamedResponse
     */
    public function getFilteredCSV($tags, $current_filter, $text, $selectedCols, $order = 'undefined')
    {

        $items = $this->preFilter($tags, $current_filter, $text, $selectedCols, $order);

        $items = $items->find();

        $response = new StreamedResponse(function () use ($items, $selectedCols) {
            $handle = fopen('php://output', 'r+');
            foreach ($items as $row) {
                $line = array();

                if ($selectedCols['id'] == 1) {
                    $line['id'] = $row['id'];
                }
                if ($selectedCols['Brand.name'] == 1) {
                    $line['brand'] = $row['Brand.name'];
                }
                if ($selectedCols['Model.name'] == 1) {
                    $line['model'] = $row['Model.name'];
                }
                if ($selectedCols['stock'] == 1) {
                    $line['stock'] = $row['stock'];
                }
                if ($selectedCols['shelf'] == 1) {
                    $line['shelf'] = $row['shelf'];
                }
                if ($selectedCols['compartment'] == 1) {
                    $line['compartment'] = $row['compartment'];
                }
                if ($selectedCols['serial_number'] == 1) {
                    $line['serialNumber'] = $row['serialNumber'];
                }
                if ($selectedCols['show_in_shop'] == 1) {
                    $line['show_in_shop'] = $row['show_in_shop'];
                }
                if ($selectedCols['has_listing'] == 1) {
                    $line['has_listing'] = $row['has_listing'];
                }
                if ($selectedCols['selling_date'] == 1) {
                    $line['selling_date'] = $row['selling_date'];
                }
                if ($selectedCols['buying_date'] == 1) {
                    $line['buying_date'] = $row['buying_date'];
                }
                if ($selectedCols['holding_time_es'] == 1) {
                    $line['holding_time_es'] = $row['holding_time_es'];
                }
                if ($selectedCols['holding_time_sv'] == 1) {
                    $line['holding_time_sv'] = $row['holding_time_sv'];
                }
                if ($selectedCols['buying_date'] == 1) {
                    $line['holding_time_ev'] = $row['holding_time_ev'];
                }
                if ($selectedCols['lamp_missing'] == 1) {
                    $line['lamp_missing'] = $row['lamp_missing'];
                }
                if ($selectedCols['selling_price'] == 1) {
                    $line['selling_price'] = $row['selling_price'];
                }
                if ($selectedCols['lamp_hours'] == 1) {
                    $line['lamp_hours'] = $row['lamp_hours'];
                }
                if ($selectedCols['Model.brightness'] == 1) {
                    $line['Model.brightness'] = $row['Model.brightness'];
                }
                if ($selectedCols['Model.contrast'] == 1) {
                    $line['Model.contrast'] = $row['Model.contrast'];
                }
                if ($selectedCols['Model.resolution'] == 1) {
                    $line['Model.resolution'] = $row['Model.resolution'];
                }

                fputcsv($handle, $line);
            }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="items_export.csv"');
        $response->headers->set('Set-Cookie', 'fileDownload=true; path=/');

        return $response;

    }

    /**
     * @param \PropelCollection $tags
     * @param string            $filter
     *
     * @return \PropelModelPager
     */
    public function getFilterFor(\PropelCollection $tags, $filter)
    {
        $items = ItemQuery::create();


        if (!$tags->isEmpty()) {
            $items = $items->filterByTag($tags, \Criteria::IN)->distinct()->groupBy($filter)->find();
        } else {
            $items = $items->distinct()->groupBy($filter)->find();
        }

        return $items;

    }

    /**
     * @param \PropelCollection $tags
     * @param array             $current_filter
     * @param string            $text
     * @param array             $selectedCols
     * @param string            $order
     *
     * @return \PropelModelPager
     */
    public function preFilter(\PropelCollection $tags, $current_filter, $text = '', $selectedCols = array(), $order = 'undefined')
    {
        $items = ItemQuery::create('i');
        $items->select(array('id', 'model_id', 'stock', 'shelf', 'compartment', 'serial_number', 'Model.id', 'Model.name', 'Brand.id', 'Brand.name', 'holding_time_es', 'holding_time_sv', 'holding_time_ev', 'show_in_shop', 'has_listing', 'selling_date', 'buying_date', 'lamp_missing', 'selling_price', 'buying_price', 'lamp_hours', 'Model.brightness', 'Model.contrast', 'Model.resolution'));

        $items->join('i.Model');
        $items->useModelQuery()->join('Brand')->endUse();

        @list($orderColumn, $orderDirection) = explode(':', $order);
        if ($orderColumn == 'undefined') {
            $orderColumn    = 'id';
            $orderDirection = 'desc';
        }

        if (!$tags->isEmpty()) {
            $items = $items->filterByTag($tags, \Criteria::IN);
        }

        if ($current_filter['brand'] != 'alle') {

            $models = ModelQuery::create()
                ->findByBrandId($current_filter['brand']);
            if (!$models->isEmpty()) {
                $items = $items->filterByModel($models, \Criteria::IN);
            }
        }

        if ($current_filter['stock'] != 'alle') {
            $items = $items->filterByStock($current_filter['stock']);
        }

        if ($current_filter['shelf'] != 'alle') {
            $items = $items->filterByShelf($current_filter['shelf']);
        }
        if ($current_filter['compartment'] != 'alle') {
            $items = $items->filterByCompartment($current_filter['compartment']);
        }

        if ($current_filter['show_in_shop'] != 'alle') {
            $items = $items->filterByShowInShop($current_filter['show_in_shop']);
        }

        if ($current_filter['has_listing'] != 'alle') {
            $items = $items->filterByHasListing($current_filter['has_listing']);
        }

        if ($current_filter['lamp_missing'] != 'alle') {
            $items = $items->filterByLampMissing($current_filter['lamp_missing']);
        }

        if ($current_filter['selling_from'] != '0000-00-00' and $current_filter['selling_from'] != 'undefined') {
            $items = $items->filterBySellingDate(array('min' => $current_filter['selling_from'], 'max' => $current_filter['selling_to']));
        }

        if ($current_filter['buying_from'] != '0000-00-00' and $current_filter['buying_to'] != 'undefined') {

            $items = $items->filterByBuyingDate(array('min' => $current_filter['buying_from'], 'max' => $current_filter['buying_to']));
        }

        $items = $items
            ->distinct()
            ->orderBy($orderColumn, $orderDirection);

        if ($text != '') {

            if ($selectedCols['Model.name'] == 1) {
                $items->condition('cond1', 'Model.name LIKE ?', '%' . $text . '%');
                $conditions[] = 'cond1';
            }

            if ($selectedCols['Brand.name'] == 1) {
                $items->condition('cond2', 'Brand.name LIKE ?', '%' . $text . '%');
                $conditions[] = 'cond2';
            }

            if ($selectedCols['serial_number'] == 1) {
                $items->condition('cond3', 'i.serial_number LIKE ?', '%' . $text . '%');
                $conditions[] = 'cond3';
            }

            if ($selectedCols['id'] == 1) {
                $items->condition('cond4', 'i.id LIKE ?', '%' . $text . '%');
                $conditions[] = 'cond4';
            }

            $items->combine($conditions, 'or', 'cond_main');
            $items = $items->where(array('cond_main'));
        }

        return $items;
    }

    /**
     * @param \PropelCollection $tags
     * @param array             $current_filter
     * @param int               $page
     * @param int               $maxPerPage
     * @param string            $text
     * @param array             $selectedCols
     * @param string            $order
     *
     * @return \PropelModelPager
     */
    public function getFilteredItems(\PropelCollection $tags, $current_filter, $page = 1, $maxPerPage = 25, $text = '', $selectedCols = array(), $order = 'undefined')
    {
        $items = $this->preFilter($tags, $current_filter, $text, $selectedCols, $order);

        return $items->paginate($page, $maxPerPage);
    }

    /**
     * @Route("/items/{id}", requirements={"id" = "\d+"})
     * @Template()
     *
     * @param Item    $item
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function singleAction(Item $item, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $tag = TagQuery::create()->findPk($request->get('tag_id'));
            if ($tag) {
                if ($request->get('checked') == 1) {
                    $item->addTag($tag);
                } else {
                    $item->removeTag($tag);
                }
                $item->save();
            }
            exit;
        }

        return $this->redirect(
            $this->generateUrl('admin_items_form', array('id' => $item->getId()))
        );

    }

    /**
     * @Route("/items/{id}/ebay", requirements={"id" = "\d+"})
     * @Template()
     *
     * @param Item    $item
     * @param Request $request
     *
     * @return array
     */
    public function ebayAction(Item $item, Request $request)
    {
        $template    = $request->get('template', 'vorlage_beamer_CI_neu_GbR_WaWi');
        $ebayListing = new EbayListing();
        $ebayListing->setItem($item);

        $form = $this->createForm(new EbayListingType($item), $ebayListing);

        $item_images  = ItemImageQuery::create()->filterByItem($item)->orderByPos()->find();
        $model_images = ModelImageQuery::create()->filterByModel($item->getModel())->orderByPos()->find();

        if ($request->isMethod('POST')) {
            $form->submit($request);

            if ($form->isValid()) {

                /** @var \Gregwar\ImageBundle\Services\ImageHandling $imageHandling */
                $imageHandling = $this->get('image.handling');
                $picture       = $imageHandling->open('gaufrette://model_images/' . $model_images[0]->getFilename());

                /** @var \Rebeam\AppBundle\Model\EbayAccount $ebayAccount */
                $ebayAccount = $form->get('ebay_account')->getData();

                /** @var \Rebeam\EbayBundle\Ebay\EbayClient $ebay */
                $ebay = $ebayAccount->getWebservice($this->get('guzzle.service_builder'));

                $command = $ebay->getCommand('add_fixed_price_item')->set('vars', array(
                    'listing'     => $ebayListing,
                    'title'       => $form->get('title')->getData(),
                    'description' => $form->get('description')->getData(),
                    'categoryId'  => $form->get('category_id')->getData(),
                    'pictureUrl'  => 'http://rebeam.spookyapps.com' . $picture->jpeg(),
                    'account'     => $ebayAccount,
                    'item'        => $item,
                ));
                $result  = $ebay->execute($command);

                if (isset($result->Errors)) {
                    foreach ($result->Errors as $error) {

                        $this->get('session')->getFlashBag()->add(
                            'ebayErrors',
                            (string)$error->LongMessage
                        );
                    }
                }

                if (isset($result->ItemID) and $result->ItemID != 0) {
                    $ebayListing->setStatus('listed');
                    $ebayListing->setListingId($result->ItemID);
                    $ebayListing->setPrice($form->get('price')->getData());
                    $ebayListing->setAdmin($this->getUser());
                    $ebayListing->save();

                    $listing = new Listing();
                    $listing->setListingId((string)$result->ItemID);
                    $listing->setPrice($form->get('price')->getData());
                    $listing->setTitle($form->get('title')->getData());
                    $listing->setCreatedAt(new \DateTime());
                    $listing->setEbayAccount($ebayAccount);
                    if (isset($result->ListingDetails) and isset($result->ListingDetails->ViewItemURL)) {
                        $listing->setUrl((string)$result->ListingDetails->ViewItemURL);
                    }
                    $listing->addItem($item);
                    $listing->save();

                    $this->get('session')->getFlashBag()->add(
                        'notice',
                        'eBay Listing #' . (string)$result->ItemID . ' erfolgreich angelegt'
                    );
                }

                return $this->redirect(
                    $this->generateUrl('admin_items_form', array('id' => $item->getId()))
                );
            }
        } else { // Not yet submitted
            $body = $this->renderView(
                'RebeamAdminBundle:Ebay:' . $template . '.html.twig', array(
                    'item'        => $item,
                    'itemImages'  => $item_images,
                    'modelImages' => $model_images
                )
            );

            $body = str_replace('/cache/', 'http://rebeam.spookyapps.com/cache/', $body);

            $form->get('description')->setData($body);
            $form->get('title')->setData($item->getModel()->getFullName());
        }

        return array(
            'item' => $item,
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/items/{id}/comments")
     * @Method("POST")
     *
     * @param Item    $item
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function addCommentAction(Item $item, Request $request)
    {
        $commentform = $this->getCommentform($item);
        $commentform->submit($request);

        if ($commentform->isValid()) {
            $commentform->getData()->save();
        }

        return $this->redirect(
            $this->generateUrl('admin_items_form',
                array('id' => $item->getId())
            )
        );
    }

    /**
     * @param \Rebeam\AppBundle\Model\Item $item
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getCommentform(Item $item)
    {
        $comment = new Itemcomment();
        $comment->setAuthor($this->getUser());
        $comment->setItem($item);

        return $this->createForm(new ItemcommentType(), $comment);
    }

    /**
     * @param Itemcomment $comment
     * @param Request     $request
     *
     * @return RedirectResponse
     * @Route("/items/comments/{id}")
     */
    public function commentAction(Itemcomment $comment, Request $request)
    {
        $item = $comment->getItem();

        if ($request->get('action') == 'delete') {
            $comment->delete();
        }

        return $this->redirect($this->generateUrl('admin_items_form', array('id' => $item->getId())));
    }

    /**
     * @Route("/items/form", name="admin_items_form")
     * @Template()
     */
    public function formAction(Request $request)
    {
        if ($request->get('id')) {
            $item          = ItemQuery::create()->findPk($request->get('id'));
            $requestedItem = $request->get('item');
            if (isset($requestedItem['model'])) {
                $model = ModelQuery::create()->findPk($requestedItem['model']);
                $item->setModel($model);
                $item->setModelId($requestedItem['model']);
            }
        } else {
            $item = new Item();
        }

        $brands = BrandQuery::create()->orderByName()->find();

        $form = $this->createForm(new ItemType($this->getUser(), $item), $item);

        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $item->save();
            }
        }

        $r_defects = array();
        $defects   = DefectQuery::create()->find();
        foreach ($defects as $defect) {
            $r_defects[] = $defect->getName();
        }

        $orders   = $item->getOrderItems();
        $comments = ItemcommentQuery::create()
            ->filterByItem($item)
            ->orderByCreatedAt()
            ->find();
        $tags     = TagQuery::create()->orderByName()->find();

        $item->updateMinSellingPrice();

        return array(
            'form'          => $form->createView(),
            'item'          => $item,
            'brands'        => $brands,
            'defects'       => $r_defects,
            'orders'        => $orders,
            'comments'      => $comments,
            'commentform'   => $this->getCommentform($item)->createView(),
            'tags'          => $tags,
            'images'        => ItemImageQuery::create()->filterByItem($item)->orderByPos()->find(),
            'ebayTemplates' => $this->getEbayTemplates(),
            'diff'          => $item->getDiff(),
        );
    }

    /**
     * @return array
     */
    private function getEbayTemplates()
    {
        $r      = array();
        $finder = new Finder();
        $finder->files()->in(__DIR__ . '/../Resources/views/Ebay');
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $r[] = substr($file->getFilename(), 0, strlen($file->getFilename()) - 10);
        }

        return $r;
    }

    /**
     * @Route("items/{id}/label/packaging")
     *
     * @param Item $item
     *
     * @return array
     */
    public function packagingLabelAction(Item $item)
    {
        /** @var \PHPPdf\Core\Facade $facade */
        $facade   = $this->get('ps_pdf.facade');
        $template = $this->renderView('RebeamAdminBundle:Items:label.pdf.twig', array('item' => $item));

        return new Response(
            $facade->render($template),
            200,
            array(
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="label_' . $item->getId() . '.pdf"'
            )
        );
    }

    /**
     * @Route("items/{id}/label/qa")
     *
     * @param Item $item
     *
     * @return array
     */
    public function qaLabelAction(Item $item)
    {
        /** @var \PHPPdf\Core\Facade $facade */
        $facade   = $this->get('ps_pdf.facade');
        $template = $this->renderView('RebeamAdminBundle:Items:label_qa.pdf.twig', array('item' => $item));

        return new Response(
            $facade->render($template),
            200,
            array(
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="label_qa_' . $item->getId() . '.pdf"'
            )
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Rebeam\AppBundle\Model\Item              $item
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @Route("items/{id}/images")
     */
    public function imagesAction(Request $request, Item $item)
    {
        if ($request->get('action') == 'delete') {
            $image_id = $request->get('image');
            if ($image_id > 0) {
                $image = ItemImageQuery::create()->findPk($image_id);
                if ($image != null) {
                    $image->delete();
                }
            }

            return $this->redirect($this->generateUrl('admin_items_form', array('id' => $item->getId())));
        }

        if ($request->isMethod('POST')) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $files = $request->files->get('images');

            /** @var \Gaufrette\Filesystem $filesystem */
            foreach ($files as $file) {
                $filesystem = $this->get('gaufrette.item_images_filesystem');

                $image = new ItemImage();
                $image->setItem($item);
                $image->setOriginalFile($file->getClientOriginalName());
                $image->save();

                $filesystem->write($image->getFilename(), file_get_contents($file->getRealPath()));
            }

            exit;

            return $this->redirect($this->generateUrl('admin_items_form', array('id' => $item->getId())));

        }

        throw new NotFoundHttpException();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Route("/items/imagesort")
     */
    public function imageSortAction(Request $request)
    {
        $i         = 0;
        $image_ids = $request->get('imageIds');

        foreach ($image_ids as $image_id) {
            $image = ItemImageQuery::create()->findPk($image_id);
            $image->setPos($i);
            $image->save();
            $i++;
        }
        exit;
    }
}
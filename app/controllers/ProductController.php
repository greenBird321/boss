<?php


/**
 * 商品管理
 */
namespace MyApp\Controllers;


use MyApp\Models\Products;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Setting;
use MyApp\Models\Utils;


class ProductController extends ControllerBase
{
    private $settingModel;
    private $productModel;
    private $utilsModel;

    public function initialize()
    {
        parent::initialize();
        $this->settingModel = new Setting();
        $this->productModel = new Products();
        $this->utilsModel = new Utils();
    }

    public function indexAction()
    {
        $gateways = $this->settingModel->getGatewaysList();
        $gateways[] = array('id'=>9999,'gateway'=>'other','name'=>'Other');

        $data = [];
        foreach ($gateways as $item) {
            $item['product'] = $this->productModel->getProductList($item['gateway']);
            $data[] = $item;
        }
        $this->view->proData = $data;
    }

    /**
     * 发布产品
     */
    public function createAction()
    {
        if ($_POST) {
            $this->productModel->app_id = $this->session->get('app');
            $this->productModel->gateway = $this->request->get('gateway', ['string', 'trim']);
            $this->productModel->name = $this->request->get('name', ['string', 'trim']);
            $this->productModel->product_id = $this->request->get('product_id', ['string', 'trim']);
            $this->productModel->package = $this->request->get('package', ['string', 'trim']);
            $this->productModel->price = $this->request->get('price', ['string', 'trim']);
            $this->productModel->currency = $this->request->get('currency', ['string', 'trim']);
            $this->productModel->coin = $this->request->get('coin', 'int');
            $this->productModel->image = $this->request->get('image', ['string', 'trim']);
            $this->productModel->remark = $this->request->get('remark', ['string', 'trim']);
            $this->productModel->status = $this->request->get('status', 'int');
            $this->productModel->sort = $this->request->get('sort', ['string', 'trim']);
            $this->productModel->create_time = $this->utilsModel->toTimeZone(date('Y-m-d H:i:s'),$this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $this->productModel->update_time = $this->utilsModel->toTimeZone(date('Y-m-d H:i:s'), $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            if (!$this->productModel->name || !$this->productModel->app_id || !$this->productModel->price) {
                Utils::tips('error', '数据不完整', '/product/index');
            }

            $data['product_id'] = $this->productModel->product_id;
            $data['gateway'] = $this->productModel->gateway;
            $data['custom'] = $this->request->get('custom', ['string', 'trim']);
            $data['price'] = $this->productModel->price;
            $data['currency'] = $this->productModel->currency;
            $data['coin'] = $this->productModel->coin;
            $data['status'] = $this->productModel->status;
            $data['sort'] = $this->productModel->sort;
            $data['name'] = $this->productModel->name;
            $data['remark'] = $this->productModel->remark;
            $data['image'] = $this->productModel->image;
            $data['package'] = $this->productModel->package;

            $this->productModel->save();
            $this->productModel->createProduct($data);

            for ($i = 1; $i <= 2; $i++) {
                $option['product_id'] = $this->productModel->product_id;
                $option['type'] = $this->request->get('type' . $i, ['string', 'trim']);
                $option['lowest'] = $this->request->get('lowest' . $i, 'int');
                $option['coin'] = $this->request->get('optioncoin' . $i, 'int');
                $option['prop'] = $this->request->get('prop' . $i, ['string', 'trim']);
                $option['start_time'] = $this->request->get('start_time' . $i, ['string', 'trim']);
                $option['end_time'] = $this->request->get('end_time' . $i, ['string', 'trim']);

                if ($option['type'] && $option['start_time'] && $option['end_time']) {
                    $this->productModel->createOption($option);
                }

                unset($option);
            }

            Utils::tips('success', '添加成功', '/product/index');
        }

        $parent = $this->settingModel->getParent();

        if (empty($parent)) {
            Utils::tips('error', '请先创建网关', '/setting/gateways?do=create');
        }
        $this->view->parent = $parent;
    }

    /**
     * 修改产品
     */
    public function editAction()
    {
        $id = $this->request->get('id', 'int');
        if (!$id) {
            Utils::tips('error', '数据不完整', '/product/index');
        }

        $product = $this->productModel->findFirst($id);
        if (!$product) {
            Utils::tips('error', '没有此数据', '/product/index');
        }
        $old_product_id = $product->product_id;
        $rpcProduct = $this->productModel->item(array('product_id' => $product->product_id));

        if ($_POST) {
            $product->app_id = $this->session->get('app');
            $product->gateway = $this->request->get('gateway', ['string', 'trim']);
            $product->name = $this->request->get('name', ['string', 'trim']);
            $product->product_id = $this->request->get('product_id', ['string', 'trim']);
            $product->package = $this->request->get('package', ['string', 'trim']);
            $product->price = $this->request->get('price', ['string', 'trim']);
            $product->currency = $this->request->get('currency', ['string', 'trim']);
            $product->coin = $this->request->get('coin', 'int');
            $product->image = $this->request->get('image', ['string', 'trim']);
            $product->remark = $this->request->get('remark', ['string', 'trim']);
            $product->status = $this->request->get('status', 'int');
            $product->sort = $this->request->get('sort', 'int');
            $product->update_time = $this->utilsModel->toTimeZone(date('Y-m-d H:i:s'), $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            if (!$product->name || !$product->app_id || !$product->price) {
                Utils::tips('error', '数据不完整', '/product/edit?id=' . $product['id']);
            }
            $data['product_id'] = $product->product_id;
            $data['gateway'] = $product->gateway;
            $data['price'] = $product->price;
            $data['currency'] = $product->currency;
            $data['coin'] = $product->coin;
            $data['status'] = $product->status;
            $data['sort'] = $product->sort;
            $data['name'] = $product->name;
            $data['remark'] = $product->remark;
            $data['image'] = $product->image;
            $data['package'] = $product->package;
            $data['custom'] = $this->request->get('custom', ['string', 'trim']);

            $product->save();

            if (empty($rpcProduct)) {
                $this->productModel->createProduct($data);
            }
            else {
                $data['old_product_id'] = $old_product_id;
                $this->productModel->editProduct($data);
            }

            for ($i = 1; $i <= 2; $i++) {
                $option['id'] = $this->request->get('optionid' . $i, 'int');
                $option['type'] = $this->request->get('type' . $i, ['string', 'trim']);
                $option['lowest'] = $this->request->get('lowest' . $i, 'int');
                $option['coin'] = $this->request->get('optioncoin' . $i, 'int');
                $option['prop'] = $this->request->get('prop' . $i, ['string', 'trim']);
                $option['start_time'] = $this->request->get('start_time' . $i, ['string', 'trim']);
                $option['end_time'] = $this->request->get('end_time' . $i, ['string', 'trim']);

                $option['start_time'] = $this->utilsModel->toTimeZone($option['start_time'], $this->config->setting->timezone,
                    $this->utilsModel->getTimeZone());
                $option['end_time'] = $this->utilsModel->toTimeZone($option['end_time'], $this->config->setting->timezone,
                    $this->utilsModel->getTimeZone());

                if ($option['id']) {
                    $this->productModel->editOption($option);
                }
                else {
                    if ($option['id'] == '' && $option['type'] && $option['start_time'] && $option['end_time']) {
                        $option['product_id'] = $product->product_id;
                        unset($option['id']);
                        $this->productModel->createOption($option);
                    }
                    else {
                        continue;
                    }
                }
                unset($option);
            }

            Utils::tips('success', '修改成功', '/product/index');
        }

        $this->view->parent = $this->settingModel->getParent();
        $this->view->pro = $product->toArray();
        $this->view->custom = $rpcProduct['data']['custom'];

        if (count($rpcProduct['data']['more']) == 1) {
            $this->view->more = $rpcProduct['data']['more'];
            $this->view->isone = 1;
            $this->view->iskong = 0;
        }
        if (count($rpcProduct['data']['more']) == 0) {
            $this->view->more = array();
            $this->view->iskong = 1;
            $this->view->isone = 0;
        }
        if (count($rpcProduct['data']['more']) == 2) {
            $this->view->more = $rpcProduct['data']['more'];
            $this->view->isone = 0;
            $this->view->iskong = 0;
        }
    }


    /**
     * 删除产品
     */
    public function removeAction()
    {
        $id = $this->request->get('id', 'int');
        if (!$id) {
            Utils::tips('error', '数据不完整', '/product/index');
        }

        $product = $this->productModel->findFirst($id);
        if (!$product) {
            Utils::tips('error', '没有此数据', '/product/index');
        }

        $rpcProduct = $this->productModel->item(array('product_id' => $product->product_id));

        if ($rpcProduct['data']['more']) {
            foreach ($rpcProduct['data']['more'] as $item) {
                $this->productModel->removeOption(array('id' => $item['id']));
            }
        }

        $product->delete();
        $this->productModel->removeProduct(array('product_id' => $product->product_id));

        Utils::tips('success', '删除成功', '/product/index');
    }

    public function removeoptionAction()
    {
        $optionid = $this->request->get('optionid', 'int');
        $productid = $this->request->get('productid', 'int');

        if (!$productid) {
            Utils::tips('error', '数据不完整', '/product/index');
        }

        if (!$optionid) {
            Utils::tips('error', '数据不完整', '/product/edit?id=' . $productid);
        }

        $this->productModel->removeOption(array('id' => $optionid));

        Utils::tips('success', '删除配置成功', '/product/edit?id=' . $productid);
    }


    /**
     * 导入产品
     */
    public function importAction()
    {

    }
}
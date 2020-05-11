<?php
/**
 * AddressController
 * @package admin-address
 * @version 0.0.1
 */

namespace AdminAddress\Controller;

use LibFormatter\Library\Formatter;
use LibForm\Library\Form;

class AddressController extends \Admin\Controller
{
    private $objects = [
        'country' => [
            'active' => false,
            'label' => 'Country',
            'model' => 'LibAddress\\Model\\AddrCountry',
            'parent' => null
        ],
        'state' => [
            'active' => false,
            'label' => 'State',
            'model' => 'LibAddress\\Model\\AddrState',
            'parent' => 'country'
        ],
        'city' => [
            'active' => false,
            'label' => 'City',
            'model' => 'LibAddress\\Model\\AddrCity',
            'parent' => 'state'
        ],
        'district' => [
            'active' => false,
            'label' => 'District',
            'model' => 'LibAddress\\Model\\AddrDistrict',
            'parent' => 'city'
        ],
        'village' => [
            'active' => false,
            'label' => 'Village',
            'model' => 'LibAddress\\Model\\AddrVillage',
            'parent' => 'district'
        ],
        'zipcode' => [
            'active' => false,
            'label' => 'Zip Code',
            'model' => 'LibAddress\\Model\\AddrZipcode',
            'parent' => 'village'
        ]
    ];

    private function makeBreadcrumb(string $type, int $parent=null): array{
        $result = [
            'Address' => '#'
        ];

        if($type === 'country')
            return $result;

        $result['Address'] = $this->router->to('adminAddressSingle', ['type'=>'country']);

        $pid    = $parent;

        $c_item = (object)$this->objects[$type];
        $c_item->type = $type;

        $parents = [];
        while(true){
            $p_item   = (object)$this->objects[$c_item->parent];
            $p_item->type = $c_item->parent;

            $p_model  = $p_item->model;
            $p_object = $p_model::getOne(['id'=>$pid]);
            if(!$p_object)
                break;

            $p_object->children = $c_item;
            $p_object->page = '#';

            if($parents)
                $p_object->page = $this->router->to('adminAddressSingle', ['type'=>$p_object->children->type], ['parent'=>$p_object->id]);

            $parents[] = $p_object;

            if(!$p_item->parent)
                break;
            else
                $pid = $p_object->{$p_item->parent};

            $c_item = $p_item;
        }

        $parents = array_reverse($parents);

        foreach($parents as $parent)
            $result[$parent->name] = $parent->page;

        return $result;
    }

    public function editAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_address)
            return $this->show404();

        $c_type = $this->req->param->type;
        if(!isset($this->objects[$c_type]))
            return $this->show404();

        $reff   = $this->req->getQuery('ref');
        if(!$reff)
            return $this->show404();

        $parent = $this->req->getQuery('parent');

        $c_item  = (object)$this->objects[$c_type];
        $c_model = $c_item->model;

        $c_object = (object)[];

        $id = $this->req->param->id;
        if($id){
            $c_object = $c_model::getOne(['id'=>$id]);
            if(!$c_object)
                return $this->show404();
        }else{
            if($c_item->parent && !$parent)
                return $this->show404();
        }

        $form = new Form('admin.addr-create');
        if(!($valid = $form->validate($c_object)) || !$form->csrfTest('noob'))
            return $this->show404();

        $set = [
            'name' => $valid->name
        ];
        if(!$id && $c_item->parent)
            $set[$c_item->parent] = $parent;

        if($id)
            $c_model::set($set, ['id'=>$id]);
        else
            $c_model::create($set);

        $this->res->redirect($reff);
    }

    public function singleAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_address)
            return $this->show404();

        $c_type = $this->req->param->type;
        if(!isset($this->objects[$c_type]))
            return $this->show404();

        $parents = null;

        $c_item  = (object)$this->objects[$c_type];
        $c_item->type = $c_type;
        $c_model = $c_item->model;

        $p_id    = (int)$this->req->getQuery('parent');

        if($c_item->parent && !$p_id)
            return $this->show404();
        if(!$c_item->parent && $p_id)
            return $this->show404();

        // get parents object
        if($c_item->parent){
            $p_item  = (object)$this->objects[$c_item->parent];
            $p_model = $p_item->model;
            $p_object= $p_model::getOne(['id'=>$p_id]);
            if(!$p_object)
                return $this->show404();

            $p_cond = [];
            if($p_item->parent)
                $p_cond[$p_item->parent] = $p_object->{$p_item->parent};

            $parents = $p_model::get($p_cond, 0, 1, ['name'=>true]);
            if($parents)
                $parents = Formatter::formatMany('addr-'.$c_item->parent, $parents);
        }

        // get current items
        $c_cond = [];
        if($p_id)
            $c_cond[ $c_item->parent ] = $p_id;

        $items = $c_model::get($c_cond, 0, 1, ['name'=>true]);
        if($items)
            $items = Formatter::formatMany('addr-' . $c_type, $items);

        // get children object
        $child = null;
        foreach($this->objects as $type => $opts){
            if($opts['parent'] == $c_type){
                $child = (object)$opts;
                $child->type = $type;
                break;
            }
        }

        $params = [
            '_meta' => [
                'title' => 'System Settings',
                'menus' => ['admin-setting']
            ],
            'bcrumb'  => $this->makeBreadcrumb($c_type, $p_id),
            'parents' => $parents,
            'parent_id' => $p_id,
            'items'   => $items,
            'item'    => $c_item,
            'child'   => $child,

            'forms'   => [
                'remove' => new Form('admin.addr-remove'),
                'create' => new Form('admin.addr-create')
            ]
        ];

        return $this->resp('address/single', $params);
    }

    public function removeAction(){
        if(!$this->user->isLogin())
            return $this->loginFirst(1);
        if(!$this->can_i->manage_address)
            return $this->show404();

        $form   = new Form('addr-remove');
        if(!$form->csrfTest('noob'))
            return $this->res->redirect($next);

        $id   = $this->req->param->id;
        $type = $this->req->param->type;
        $pid  = $this->req->getQuery('parent');

        if(!isset($this->objects[$type]))
            return $this->show404();

        $item = (object)$this->objects[$type];
        $model= $item->model;

        $cond = [
            'id' => $id
        ];
        if($item->parent)
            $cond[ $item->parent ] = $pid;

        $object = $model::getOne($cond);
        if(!$object)
            return $this->show404();

        // add the log
        $this->addLog([
            'user'   => $this->user->id,
            'object' => $id,
            'parent' => $pid,
            'method' => 3,
            'type'   => 'addr-' . $type,
            'original' => $object,
            'changes'  => null
        ]);

        $model::remove(['id'=>$id]);

        $next = $this->router->to('adminAddressSingle', ['type'=>$type], ['parent'=>$pid]);

        $this->res->redirect($next);
    }
}
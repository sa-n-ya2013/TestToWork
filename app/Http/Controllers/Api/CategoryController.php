<?php

namespace App\Http\Controllers\Api;

use Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Validator;
use Gate;
use DB;
use App\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends ApiController
{
    const FIELD_ID = 'id';
    const FIELD_NAME = 'name';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_PARENT_ID = 'parent_id';

    /**
     * Возврат списка категорий
     *
     * @param Request $request
     * @return Response
     * @throws NotFoundHttpException
     */
    public function get(Request $request)
    {
        $parent_id = (int)$request->input(self::FIELD_PARENT_ID, 0);

        if ($parent_id != 0) {
            $category = Category::with('children')->findOrFail($parent_id);
            if ($category->isDelete()) {
                throw new NotFoundHttpException();
            }

            return $this->sendOK(
                $category->children->toArray()
            );
        } else {
            return $this->sendOK(
                Category::where('parent_id', 0)->get()->toArray()
            );
        }
    }

    /**
     * Создание категории
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $params = [
            'name' => $request->input(self::FIELD_NAME, ''),
            'description' => $request->input(self::FIELD_DESCRIPTION, ''),
            'parent_id' => (int)$request->input(self::FIELD_PARENT_ID, 0),
            'user_id' => Auth::user()->id
        ];

        $validateResult = $this->validateCategory($params);
        if ($validateResult) {
            return $this->sendFail($validateResult);
        }

        $category = Category::create($params);
        if ($category) {
            return $this->sendOK($category->id);
        } else {
            return $this->sendFail();
        }
    }

    /**
     * Изменение категории
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $params = [
            'id' => (int)$request->input(self::FIELD_ID, 0),
            'name' => $request->input(self::FIELD_NAME, ''),
            'description' => $request->input(self::FIELD_DESCRIPTION, ''),
            'parent_id' => (int)$request->input(self::FIELD_PARENT_ID, 0),
        ];

        $validateResult = $this->validateCategory($params);
        if ($validateResult) {
            return $this->sendFail($validateResult);
        }

        $category = Category::findOrFail($params['id']);
        if (Gate::denies('has-category', $category)) {
            throw new AccessDeniedHttpException();
        }

        if (!is_null($request->get(self::FIELD_NAME))) $category->name = $params['name'];
        if (!is_null($request->get(self::FIELD_DESCRIPTION))) $category->description = $params['description'];
        if (!is_null($request->get(self::FIELD_PARENT_ID))) $category->parent_id = $params['parent_id'];

        if ($category->save()) {
            return $this->sendOK($category->id);
        } else {
            return $this->sendFail();
        }
    }

    /**
     * Удаление категории
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request)
    {
        $id = (int)$request->input(self::FIELD_ID, 0);
        $category = Category::findOrFail($id);

        if ($category->isDelete()) {
            throw new NotFoundHttpException();
        }
        if (Gate::denies('has-category', $category)) {
            throw new AccessDeniedHttpException();
        }

        DB::beginTransaction();
        if ($category->delete()) {
            DB::commit();
            return $this->sendOK();
        } else {
            DB::rollback();
            return $this->sendFail();
        }
    }

    /**
     * Валидация категории
     *
     * @param array $params
     * @return array
     */
    private function validateCategory(array $params)
    {
        $validator = Validator::make($params, [
            'name' => 'required',
            'parent_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return (array) $validator->errors();
        }

        if ($params['parent_id'] > 0) {
            $category = Category::findOrFail($params['parent_id']);
            if (Gate::denies('has-category', $category)) {
                throw new AccessDeniedHttpException();
            }
            if ($category->isDelete()) {
                return ['parent_id' => ['Category not found']];
            }
        }

        return [];
    }
}
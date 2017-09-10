<?php

namespace App\Http\Controllers\Api;

use App\Good;
use App\Tag;
use Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Validator;
use Gate;
use DB;
use App\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GoodController extends ApiController
{
    const FIELD_ID = 'id';
    const FIELD_NAME = 'name';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_CATEGORY_ID = 'category_id';
    const FIELD_PRICE = 'price';
    const FIELD_IMAGE = 'image';
    const FIELD_IMAGE_DESCRIPTION = 'image_description';
    const FIELD_TAGS = 'tags';

    /**
     * Возврат списка товаров
     *
     * @param Request $request
     * @return Response
     * @throws NotFoundHttpException
     */
    public function get(Request $request)
    {
        $category_id = $request->input(self::FIELD_CATEGORY_ID);
        $name = $request->input(self::FIELD_NAME);
        $description = $request->input(self::FIELD_DESCRIPTION);
        $tags = $request->input(self::FIELD_TAGS, []);

        $goodQuery = Good::with('tags');

        if (!is_null($category_id)) where('category_id', (int)$category_id);
        if (!is_null($name)) $goodQuery->where('name', 'like', "%$name%");
        if (!is_null($description)) $filter[] = ['description', 'like', "%$description%"];
        if (count($tags)) {
            $goodsByTags = Tag::whereIn('tag', $tags)->select('good_id')->distinct()->get();
            $goodIds = [];
            foreach ($goodsByTags as $good) {
                $goodIds[] = $good->good_id;
            }
            $goodQuery->whereIn('id', $goodIds);
        }
        return $this->sendOK(
            $goodQuery->get()->toArray()
        );
    }

    /**
     * Создание товара
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $params = [
            'name' => $request->input(self::FIELD_NAME),
            'description' => $request->input(self::FIELD_DESCRIPTION, ''),
            'category_id' => (int)$request->input(self::FIELD_CATEGORY_ID, 0),
            'price' => (int)$request->input(self::FIELD_PRICE),
            'user_id' => Auth::user()->id,
            'tags' => $request->input(self::FIELD_TAGS, []),
        ];

        $validateResult = $this->validateGood($params);
        if ($validateResult) {
            return $this->sendFail($validateResult);
        }

        DB::beginTransaction();
        $good = new Good($params);
        $good->category_id = $params['category_id'];
        $good->price = $params['price'];

        if (!$good->save()) {
            DB::rollback();
            return $this->sendFail();
        }
        foreach ($params['tags'] as $tag) {
            $tagObj = new Tag;
            $tagObj->good_id = $good->id;
            $tagObj->tag = $tag;
            if (!$tagObj->save()) {
                DB::rollback();
                return $this->sendFail();
            }
        }
        DB::commit();
        return $this->sendOK($good->id);
    }

    /**
     * Изменение товара
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $params = [
            'id' => $request->input(self::FIELD_ID),
            'name' => $request->input(self::FIELD_NAME),
            'description' => $request->input(self::FIELD_DESCRIPTION),
            'category_id' => (int)$request->input(self::FIELD_CATEGORY_ID),
            'price' => (int)$request->input(self::FIELD_PRICE),
            'tags' => $request->input(self::FIELD_TAGS),
        ];

        $validateResult = $this->validateGood($params);
        if ($validateResult) {
            return $this->sendFail($validateResult);
        }

        DB::beginTransaction();
        $good = Good::with('category')->findOrFail($params['id']);

        if (Gate::denies('has-category', $good->category)) {
            throw new AccessDeniedHttpException();
        }

        if (!is_null($params['category_id'])) $good->category_id = $params['category_id'];
        if (!is_null($params['name'])) $good->name = $params['name'];
        if (!is_null($params['description'])) $good->description = $params['description'];
        if (!is_null($params['price'])) $good->price = $params['price'];

        if (!$good->save()) {
            DB::rollback();
            return $this->sendFail();
        }
        if (!is_null($params['tags'])) {
            if (!Tag::where('good_id', $good->id)->delete()) {
                DB::rollback();
                return $this->sendFail();
            }
            foreach ($params['tags'] as $tag) {
                $tagObj = new Tag;
                $tagObj->good_id = $good->id;
                $tagObj->tag = $tag;
                if (!$tagObj->save()) {
                    DB::rollback();
                    return $this->sendFail();
                }
            }
        }
        DB::commit();
        return $this->sendOK($good->id);
    }

    /**
     * Удаление товара
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request)
    {
        $id = (int)$request->input(self::FIELD_ID, 0);
        $good = Good::with('category')->findOrFail($id);

        if (is_null($good->category) || $good->category->isDelete()) {
            throw new NotFoundHttpException();
        }
        if (Gate::denies('has-category', $good->category)) {
            throw new AccessDeniedHttpException();
        }

        if ($good->delete()) {
            return $this->sendOK();
        } else {
            return $this->sendFail();
        }
    }

    /**
     * Валидация товара
     *
     * @param array $params
     * @return array
     */
    private function validateGood(array $params)
    {
        $validator = Validator::make($params, [
            'name' => 'required',
            'category_id' => 'required|integer',
            'price' => 'required|integer',
            'tags' => 'array',
        ]);
        if ($validator->fails()) {
            return (array) $validator->errors();
        }

        $category = Category::findOrFail($params['category_id']);
        if (Gate::denies('has-category', $category)) {
            throw new AccessDeniedHttpException();
        }
        if ($category->isDelete()) {
            return ['category_id' => ['Category not found']];
        }

        return [];
    }
}
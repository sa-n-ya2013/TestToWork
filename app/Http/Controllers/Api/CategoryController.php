<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends ApiController
{
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

    public function create(Request $request)
    {
//        var_dump($request->getSession());exit;
        Category::create([
            'name' => $request->input(self::FIELD_NAME, ''),
            'description' => $request->input(self::FIELD_DESCRIPTION, ''),
            'parent_id' => $request->input(self::FIELD_PARENT_ID, 0),
            'user_id' => Auth::user()->id
        ]);
    }
}
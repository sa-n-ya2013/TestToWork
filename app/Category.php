<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'user_id', 'parent_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at', 'updated_at', 'parent_id', 'user_id'
    ];

    /**
     * Проверяет категорию на удаление. Если одна из родительских категорий удалена, то эта тоже считается удаленной.
     */
    public function isDelete()
    {
        $parents = $this->parents();
        foreach($parents as $parent) {
            if (!is_null($parent->deleted_at)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает список родительских категорий, включая удаленные
     *
     * @return array
     */
    public function parents()
    {
        $parent = Category::withTrashed()->find($this->parent_id);
        if (is_null($parent)) {
            return [$this];
        }
        return array_merge([$this], $parent->parents());
    }

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function goods()
    {
        return $this->hasMany(Good::class);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function delete()
    {
        foreach ($this->children as $child){
            if (!$child->delete()) {
                return false;
            }
        }
        foreach ($this->goods as $good){
            if (!$good->delete()) {
                return false;
            }
        }

        return parent::delete();
    }
}

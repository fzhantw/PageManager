<?php

namespace Backpack\PageManager\app\Models;

use Backpack\LangFileManager\app\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\CrudTrait;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class Page extends Model
{
    use CrudTrait;
    use Sluggable;
    use SluggableScopeHelpers;

     /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'pages';
    protected $primaryKey = 'id';
    public $timestamps = true;
    // protected $guarded = ['id'];
    protected $fillable = ['template', 'title', 'slug', 'content', 'name', 'description', 'extras'];
    // protected $hidden = [];
    // protected $dates = [];
    protected $fakeColumns = ['extras'];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
        'description' => 'array',
        'extras' => 'array',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'slug_or_title',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function getTemplateName()
    {
        return trim(preg_replace('/(id|at|\[\])$/i', '', ucfirst(str_replace('_', ' ', $this->template))));
    }

    public function getPageLink($lang = null)
    {
        if (!$lang) {
            $lang = Language::getDefault()->abbr;
        }

        return route('page', ['lang' => $lang, 'page' => $this->slug]);
    }

    public function getOpenButton()
    {
        return '<a class="btn btn-default btn-xs" href="'.$this->getPageLink().'" target="_blank"><i class="fa fa-eye"></i> 開啟</a>';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    // The slug is created automatically from the "name" field if no slug exists.
    public function getSlugOrTitleAttribute()
    {
        if ($this->slug != '') {
            return $this->slug;
        }

        return $this->title;
    }

    public function getTitle($lang_id = null)
    {
        if (!$lang_id) {
            $lang_id = Language::getDefault();
        }

        return $this->title[$lang_id];
    }

    public function getContent($lang_id = null)
    {
        if (!$lang_id) {
            $lang_id = Language::getDefault()->id;
        }

        if (array_key_exists($lang_id, $this->content)) {
            return $this->content[$lang_id];
        } else {
            $lang = Language::find($lang_id);
            return view('pages.content.' . $this->name . '_' . $lang->abbr . '.blade');
        }
    }

    public function getDescription($lang_id = null)
    {
        if (!$lang_id) {
            $lang_id = Language::getDefault();
        }

        return $this->description[$lang_id];
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function __get($name)
    {
        if (preg_match('/(.+)\[(\d+)\]/', $name, $matches)) {
            $value = parent::__get($matches[1]);

            return array_key_exists($matches[2], $value) ? $value[$matches[2]]: '';
        }

        return parent::__get($name);
    }
}

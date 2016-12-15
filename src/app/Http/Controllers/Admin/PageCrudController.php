<?php

namespace Backpack\PageManager\app\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
// VALIDATION: change the requests to match your own file names if you need form validation
use Backpack\LangFileManager\app\Models\Language;
use Backpack\PageManager\app\Http\Requests\PageRequest as StoreRequest;
use Backpack\PageManager\app\Http\Requests\PageRequest as UpdateRequest;
use App\PageTemplates;
use Backpack\PageManager\app\Models\Page;
use Illuminate\Http\Request;

class PageCrudController extends CrudController
{
    use PageTemplates;

    public function __construct($template_name = false)
    {
        parent::__construct();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel("Backpack\\PageManager\\app\\Models\\Page");
        $this->crud->setRoute(config('backpack.base.route_prefix').'/page');
        $this->crud->setEntityNameStrings('page', 'pages');

        /*
        |--------------------------------------------------------------------------
        | COLUMNS
        |--------------------------------------------------------------------------
        */

        $this->crud->addColumn([
                                'name' => 'title',
                                'type' => 'model_function',
                                'function_name' => 'getTitle',
                                ]);
        $this->crud->addColumn('published');
        $this->crud->addColumn('slug');

        /*
        |--------------------------------------------------------------------------
        | FIELDS
        |--------------------------------------------------------------------------
        */

        // In PageManager,
        // - default fields, that all templates are using, are set using $this->addDefaultPageFields();
        // - template-specific fields are set per-template, in the PageTemplates trait;


        /*
        |--------------------------------------------------------------------------
        | BUTTONS
        |--------------------------------------------------------------------------
        */
        $this->crud->addButtonFromModelFunction('line', 'open', 'getOpenButton', 'beginning');
        $this->crud->addButtonFromModelFunction('line', 'open', 'getPublishButton', 'open');
    }

    // -----------------------------------------------
    // Overwrites of CrudController
    // -----------------------------------------------

    // Overwrites the CrudController create() method to add template usage.
    public function create($template = false)
    {
        $request = app('request');
        $this->setFields($template, $request);

        return parent::create();
    }

    // Overwrites the CrudController store() method to add template usage.
    public function store(StoreRequest $request)
    {
        $template = $request->input('template');

        $this->setFields($template, $request);

        return parent::storeCrud();
    }

    // Overwrites the CrudController edit() method to add template usage.
    public function edit($id, $template = false)
    {
        // if the template in the GET parameter is missing, figure it out from the db
        if ($template == false) {
            $model = $this->crud->model;
            $this->data['entry'] = $model::findOrFail($id);
            $template = $this->data['entry']->template;
        }

        $this->setFields($template, app('request'));

        return parent::edit($id);
    }

    // Overwrites the CrudController update() method to add template usage.
    public function update(UpdateRequest $request)
    {
        $template = $request->input('template');

        $this->setFields($template, $request);

        return parent::updateCrud();
    }

    // -----------------------------------------------
    // Methods that are particular to the PageManager.
    // -----------------------------------------------

    /**
     * Populate the create/update forms with basic fields, that all pages need.
     *
     * @param string $template The name of the template that should be used in the current form.
     */
    public function addDefaultPageFields($template = false)
    {
        $this->crud->addField([
                                'name' => 'template',
                                'label' => '樣板',
                                'type' => 'select_page_template',
                                'options' => $this->getTemplatesArray(),
                                'value' => $template,
                                'allows_null' => false,
                                'wrapperAttributes' => [
                                    'class' => 'form-group col-md-6',
                                ],
                            ]);
        $this->crud->addField([
                                'name' => 'name',
                                'label' => '後台辨識用',
                                'type' => 'text',
                                'wrapperAttributes' => [
                                    'class' => 'form-group col-md-6',
                                ],
                                // 'disabled' => 'disabled'
                            ]);
        $this->crud->addField([
                                'name' => 'slug',
                                'label' => 'Page Slug (URL)',
                                'type' => 'text',
                                'hint' => 'Will be automatically generated from your title, if left empty.',
                                // 'disabled' => 'disabled'
                            ]);
        $this->crud->addField([
            'name' => 'feature_image',
            'label' => '特色圖片',
            'type' => 'browse',
            'hint' => '建議尺寸1200 x 630.',
            'fake' => true,
            'store_in' => 'extras',
        ]);
        $this->crud->addField([
            'name' => 'published',
            'label' => '發佈',
            'type' => 'checkbox',
            'hint' => '發佈的頁面才會被加入靜態版本裡.',
            'fake' => false,
        ]);
    }

    /**
     * Add the fields defined for a specific template.
     *
     * @param  string $template_name The name of the template that should be used in the current form.
     */
    public function useTemplate($template_name = false, $lang)
    {
        $templates = $this->getTemplates();

        // set the default template
        if ($template_name == false) {
            $template_name = $templates[0]->name;
        }

        // actually use the template
        if ($template_name) {
            $this->{$template_name}($lang);
        }
    }

    /**
     * Get all defined templates.
     */
    public function getTemplates()
    {
        $templates_array = [];

        $templates_trait = new \ReflectionClass('App\PageTemplates');
        $templates = $templates_trait->getMethods();

        if (! count($templates)) {
            abort('403', 'No templates have been found.');
        }

        return $templates;
    }

    /**
     * Get all defined template as an array.
     *
     * Used to populate the template dropdown in the create/update forms.
     */
    public function getTemplatesArray()
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            $templates_array[$template->name] = $this->crud->makeLabel($template->name);
        }

        return $templates_array;
    }

    /**
     * @return array
     */
    public function getActiveLangs()
    {
        return Language::getActiveLanguagesArray();
    }

    public function publish($id)
    {
        $page = Page::find($id);

        $page->update([
            'published' => true,
        ]);

        return  redirect()->back();
    }

    public function unpublish($id)
    {
        $page = Page::find($id);

        $page->update([
            'published' => false,
        ]);

        return  redirect()->back();
    }
    protected function setFields($template, $request)
    {
        $this->addDefaultPageFields($template);

        $langs = $this->getActiveLangs();

        foreach ($langs as $lang) {
            $this->useTemplate($template, $lang);
        }
    }
}

<?php

namespace LaravelAdmin\Crud\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LaravelAdmin\Crud\Traits\CanBeSecured;
use LaravelAdmin\Crud\Traits\Crud;

class LayoutController extends Controller
{
    use Crud, CanBeSecured;

    protected $config;

    public function __construct()
    {
        $this->config = (property_exists($this, 'layout_config')) ? $this->layout_config : 'layout';
    }

    /**
     * Show the layout
     * @param  int $page_id
     * @return Response;
     */
    public function show(Request $request, $id, $translation)
    {
        $this->checkRole();

        $field = (config("{$this->config}.field")) ?: 'layout';

        //	Get the model instance
        $parent = $this->getModelInstance($id);
        $select_parent_name = (property_exists($this, 'parent_name')) ? $this->parent_name : 'name';
        $parent_name = $parent->$select_parent_name;
        $has_translation = $parent->hasTranslation($translation);
        $model = $parent->translateOrNew($translation);
        $foreign_key = snake_case(class_basename($this->model)) . '_id';

        if ($request->has('copy')) {
            if ($copyfrom = $this->getModelInstance($id)->translateOrNew($request->copy)) {
                $model->fill([$field => $copyfrom->$field]);
                $model->save();

                $this->flash('The layout is succesfully copied', 'success');

                return back();
            }
        }

        $model->$foreign_key = $id;

        if ($request->ajax()) {
            return $model;
        }

        $settings = (new \LaravelAdmin\Crud\Layout\Config($this->config))->all();

        //	Render the view
        return view('crud::templates.layout', $this->parseViewData(compact('select_parent_name', 'parent_name', 'model', 'field', 'settings', 'has_translation', 'translation', 'foreign_key')));
    }

    /**
     * Store the layout
     * @param  Request $request [description]
     * @param  int  $page_id [description]
     * @return Response
     */
    public function update(Request $request, $id, $translation)
    {
        $this->checkRole();

        $field = (config("{$this->config}.field")) ?: 'layout';

        //	Validate the request with the specified validation rules and messages
        $validation = $this->setupValidation(['layout' => []]);
        $this->validate($request, $validation['rules'], $validation['messages']);

        //	Get the model instance
        $model = $this->getModelInstance($id);

        $payload = [$field => $request->layout];

        // Add user_id to payload
        if (\Schema::hasColumn($this->model()->getTable(), 'updated_by')) {
            $payload['updated_by'] = \Auth::user()->id;
        }
        $model->translateOrNew($translation)->fill($payload);
        $model->save();

        $this->flash('The layout is succesfully saved.', 'success');

        return ['status' => 'success'];
    }

    private function setupValidation(array $default)
    {
        $validation_rules = $default;
        $validation_messages = [];
        $config = config("{$this->config}");
        foreach ($config['components'] as $value) {
            foreach ($value['fields'] as $field) {
                if ($field['type'] === 'layout-repeater') {
                    foreach ($field['children'] as $child_field) {
                        if (isset($child_field['validate_rule'])) {
                            $validation_rules[] = [
                                "layout.*.content.{$field['id']}.*.{$child_field['id']}" => $child_field['validate_rule']
                            ];

                            if (isset($child_field['validate_message'])) {
                                foreach ($child_field['validate_message'] as $rule => $message) {
                                    $validation_messages[] = [
                                        "layout.*.content.{$field['id']}.*.{$child_field['id']}.{$rule}" => $message
                                    ];
                                }
                            } else {
                                $name = strtolower($child_field['name']);
                                $validation_messages[] = [
                                    "layout.*.content.{$field['id']}.*.{$child_field['id']}.required" => "The {$name} field is required."
                                ];
                            }
                        }
                    }
                } elseif ($field['type'] === 'layout-component-repeater') {
                    foreach ($field['children'] as $child) {
                        foreach ($child['fields'] as $child_field) {
                            if (isset($child_field['validate_rule'])) {
                                $validation_rules[] = [
                                    "layout.*.content.{$field['id']}.*.content.{$child_field['id']}" => $child_field['validate_rule']
                                ];

                                if (isset($child_field['validate_message'])) {
                                    foreach ($child_field['validate_message'] as $rule => $message) {
                                        $validation_messages[] = [
                                            "layout.*.content.{$field['id']}.*.content.{$child_field['id']}.{$rule}" => $message
                                        ];
                                    }
                                } else {
                                    $name = strtolower($child_field['name']);
                                    $validation_messages[] = [
                                        "layout.*.content.{$field['id']}.*.content.{$child_field['id']}.required" => "The {$name} field is required."
                                    ];
                                }
                            }
                        }
                    }
                } elseif (isset($field['validate_rule'])) {
                    $validation_rules[] = [
                        "layout.*.content.{$field['id']}" => $field['validate_rule']
                    ];

                    if (isset($field['validate_message'])) {
                        foreach ($field['validate_message'] as $rule => $message) {
                            $validation_messages[] = [
                                "layout.*.content.{$field['id']}.{$rule}" => $message
                            ];
                        }
                    } else {
                        $name = strtolower($field['name']);
                        $validation_messages[] = [
                            "layout.*.content.{$field['id']}.required" => "The {$name} field is required."
                        ];
                    }
                }
            }
        }

        return [
            'rules' => array_collapse($validation_rules),
            'messages' => array_collapse($validation_messages)
        ];
    }
}

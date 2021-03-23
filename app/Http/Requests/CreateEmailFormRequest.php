<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmailFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sender' => ['sometimes', 'email',],
            'recipients' => ['bail', 'required', 'array'],
            'recipients.*' => ['required', 'string', 'email',],
            'subject' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'text_content' => ['sometimes', 'required_without:html_content', 'string', 'max:1500',],
            'html_content' => ['sometimes', 'required_without:text_content',],
            'attachments' => ['sometimes', 'array',],
            'attachments.*' => ['required_with:attachments', 'mimes:csv,txt,xls,xlsx,doc,docx,pdf,jpeg,png,jpg', 'max:4096'],
        ];
    }

    public function prepareForValidation()
    {
        $content = [];

        foreach (['text_content', 'subject', 'text_content', 'html_content'] as $key) {
            if ($this->has($key)) {
                $content[$key] = strip_tags($this->input($key));
            }
        }

        $this->merge($content);
    }
}
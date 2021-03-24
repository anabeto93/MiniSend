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
            'text_content' => ['required_without:html_content', 'string', 'max:1500',],
            'html_content' => ['required_without:text_content',],
            'attachments' => ['sometimes', 'array',],
            'attachments.*' => ['required_with:attachments', 'file', 'mimes:csv,txt,xls,xlsx,doc,docx,pdf,jpeg,png,jpg', 'max:4096'],
        ];
    }

    public function prepareForValidation()
    {
        $content = [];

        foreach (['text_content', 'subject',] as $key) {
            if ($this->has($key)) {
                $content[$key] = strip_tags($this->input($key));
            }
        }

        $this->merge($content);
    }
}

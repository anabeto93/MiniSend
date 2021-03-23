@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if($email->text_content)
                    {{ $email->text_content }}
                @else
                    {!! $email->html_content !!}
                @endif
            </div>
        </div>
    </div>
@endsection

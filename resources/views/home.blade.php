@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <email-form api_token="{{ $user->api_token }}"></email-form>
            <emails  :emails-updated="getEmails"  api_token="{{ $user->api_token }}"></emails>
        </div>
    </div>
</div>
@endsection

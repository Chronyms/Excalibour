@extends('layouts.app')

@section('template_title')
    {{ Auth::user()->scoutname ? Auth::user()->scoutname : Auth::user()->first_name }}'s' Homepage
@endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-sm-12">

                @include('panels.welcome-panel')

            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
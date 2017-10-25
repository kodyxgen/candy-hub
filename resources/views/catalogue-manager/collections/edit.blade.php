@extends('layout')

@section('side_menu')
    @include('catalogue-manager.partials.side-menu')
@endsection

@section('header_title')
    <small>Catalogue Manager</small>
    <h1>@verbatim<template v-if="title">{{ title }}</template>@endverbatim</h1>
@endsection

@section('header_actions')
    <candy-button style="display: inline-block;" event="save-product">Save Collection</candy-button>
    <button class="btn btn-default white product-menu btn-pop-over"><span class="hamburger"></span></button>
    <!-- Menu Pop Over -->
    <div class="pop-over">
      <ul class="menu">
        <li><a href="#" title="Duplicate product">Duplicate</a></li>
        <li><a href="#" title="View product on live site">View</a></li>
      </ul>
    </div>
    <button class="btn btn-default white"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
@endsection

@section('content')
  <candy-collection-edit id="{{ $id }}"></candy-collection-edit>
@endsection
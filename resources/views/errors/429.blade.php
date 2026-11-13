@extends('errors._branded')
@section('title', 'Muitas tentativas')
@section('icon', '✋')
@section('heading', 'Muitas tentativas em pouco tempo')
@section('message', 'Para proteger a sua conta, pausamos por alguns instantes. Aguarde cerca de 1 minuto e tente novamente.')
@section('actions')
    <a class="btn" href="javascript:history.back()">Voltar</a>
@endsection

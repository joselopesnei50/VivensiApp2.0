@extends('errors._branded')
@section('title', 'Sessão expirada')
@section('icon', '⏳')
@section('heading', 'Sua sessão expirou')
@section('message', 'Por segurança, a página ficou aberta tempo demais sem uso. Volte e tente de novo — se estava preenchendo um formulário, confira os dados antes de enviar.')
@section('actions')
    <a class="btn" href="javascript:history.back()">Voltar</a>
    <a class="btn ghost" href="{{ url('/login') }}">Entrar de novo</a>
@endsection

@extends('emails.common_cuenta_corriente', ['id_contrato' => (empty($id_contrato) ? NULL : $id_contrato)])

@section('content')

{!! $html !!}

@endsection

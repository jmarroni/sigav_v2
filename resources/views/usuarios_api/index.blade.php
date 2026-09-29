@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('usuarios_api_msg'))
        <div class="alert alert-success">{{ session('usuarios_api_msg') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Nuevo usuario de API</h3>
        </div>
        <div class="block-content">
            <form action="/usuarios-api" method="post" autocomplete="off">
                @csrf
                <div class="row">
                    <div class="col-xs-4">
                        <label>Nombre</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-xs-4">
                        <label>Email (usuario para el login de la API)</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-xs-4">
                        <label>Clave (mínimo 12 caracteres)</label>
                        <input type="password" class="form-control" name="password" minlength="12" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary" style="margin:10px 0 20px;">Crear usuario</button>
            </form>
        </div>
    </div>

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Usuarios de API</h3>
        </div>
        <div class="block-content">
            @if($usuarios->isEmpty())
                <p class="text-muted">Todavía no hay usuarios de API.</p>
            @endif

            @foreach($usuarios as $usuario)
                @php
                    $idsAsignadas = $asignadas->get($usuario->id, collect())->pluck('sucursal_id')->all();
                @endphp
                <div class="block block-bordered">
                    <div class="block-content">
                        <form action="/usuarios-api/{{ $usuario->id }}" method="post" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-xs-3">
                                    <label>Nombre</label>
                                    <input type="text" class="form-control" name="name" value="{{ $usuario->name }}" required>
                                </div>
                                <div class="col-xs-4">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email" value="{{ $usuario->email }}" required>
                                </div>
                                <div class="col-xs-3">
                                    <label>Nueva clave</label>
                                    <input type="password" class="form-control" name="password" minlength="12" autocomplete="new-password" placeholder="Vacío = no cambia">
                                </div>
                                <div class="col-xs-2">
                                    <button type="submit" class="btn btn-sm btn-default" style="margin-top:25px;">Guardar</button>
                                </div>
                            </div>
                        </form>

                        <div class="row" style="margin:15px 0;">
                            <div class="col-xs-6">
                                <label>Sucursales permitidas</label>
                                <div>
                                    @forelse($sucursales->whereIn('id', $idsAsignadas) as $sucursal)
                                        <form action="/usuarios-api/{{ $usuario->id }}/sucursales/{{ $sucursal->id }}" method="post" style="display:inline-block;margin:0 5px 5px 0;">
                                            @csrf
                                            @method('DELETE')
                                            <span class="label label-info">{{ $sucursal->nombre }}</span>
                                            <button type="submit" class="btn btn-xs btn-link" title="Quitar sucursal">quitar</button>
                                        </form>
                                    @empty
                                        <span class="text-muted">Ninguna: el usuario no ve productos.</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <form action="/usuarios-api/{{ $usuario->id }}/sucursales" method="post" class="form-inline">
                                    @csrf
                                    <select name="sucursal_id" class="form-control input-sm">
                                        @foreach($sucursales->whereNotIn('id', $idsAsignadas) as $sucursal)
                                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Agregar sucursal</button>
                                </form>
                            </div>
                            <div class="col-xs-2 text-right">
                                <form action="/usuarios-api/{{ $usuario->id }}" method="post"
                                      onsubmit="return confirm('¿Eliminar el usuario de API? Se revocan sus tokens.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

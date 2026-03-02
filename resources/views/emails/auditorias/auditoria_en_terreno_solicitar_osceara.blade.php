<table style="height: 148px; width: 568px;">
    <tbody>
        <tr>
            <td style="width: 558px;">
                <h1 style="text-align: center;">
                    <span style="color: #000000;">
                        <span style="color: #3366ff;">
                            OSCEARA
                        </span> 
                        <span style="color: #999999;">
                            SISTEMA&nbsp;
                        </span>
                    </span>
                    <span style="color: #999999;">
                        ASISTENCIAL
                    </span>
                </h1>
                <h3 style="text-align: center;">
                    <span style="color: #3366ff;">
                        Informaci&oacute;n
                    </span> 
                    <span style="color: #999999;">
                        digital
                    </span>
                </h3>
            </td>
        </tr>
    </tbody>
</table>
<table class="body-wrap" 
    style="width: 554px; bgcolor=''">
    <tbody>
        <tr>
            <td style="width: 10px;">
                &nbsp;
            </td>
            <td class="container" 
                style="width: 528px; align='' bgcolor='#FFFFFF'">
            </td>
            <div class="content">
                &nbsp;
            </div>
            <div class="content">
                <div class="content">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="detalle-titulo" 
                                        style="text-align: center;">
                                        <strong>
                                            <span style="color: #3366ff;">
                                                AUDITORIA
                                                <span style="color: #999999;">
                                                    EN TERRENO
                                                </span>
                                            </span>
                                        </strong>
                                    </div>
                                    <div class="noticia">
                                    <p style="text-align: justify;">
                                            {{ $parametros_mensaje['solicitud'] }}
                                            <strong>
                                                {{ $parametros_mensaje['solicitud_strong'] }}
                                            </strong>
                                        </p>
                                        <p style="text-align: justify;">
                                            ID Requerimiento: 
                                            <strong>
                                                {{ $parametros_mensaje['id_requerimiento'] }}
                                            </strong>
                                        </p>
                                        <p style="text-align: justify;">
                                            Prestador:
                                            <strong>
                                                {{ $parametros_mensaje['prestador'] }}
                                            </strong>
                                        </p>
                                        <p style="text-align: justify;">
                                            Sector: 
                                            <strong>
                                                {{ $parametros_mensaje['sector'] }}
                                            </strong>
                                            ,   Piso: 
                                            <strong>
                                                {{ $parametros_mensaje['piso'] }}
                                            </strong>
                                            ,   Habitación: 
                                            <strong>
                                                {{ $parametros_mensaje['habitacion'] }}
                                            </strong> 
                                        </p>
                                        <p>
                                            Afiliado: 
                                            <strong>
                                                {{ $parametros_mensaje['afiliado'] }}
                                            </strong>
                                            ,   Nombre: 
                                            <strong>
                                                {{ $parametros_mensaje['n_afiliado'] }}
                                            </strong>
                                        </p>
                                        <p>
                                            Sexo: 
                                            <strong>
                                                {{ $parametros_mensaje['sexo'] }}
                                            </strong>
                                            ,   Fecha de Nacimiento: 
                                            <strong>
                                                {{ $parametros_mensaje['fec_nac'] }}
                                            </strong>
                                            ,   edad: 
                                            <strong>
                                                {{ $parametros_mensaje['edad'] }} 
                                            </strong>
                                            , &nbsp;&nbsp;
                                            <strong>
                                                {{ $parametros_mensaje['tipo_doc'] }}: 
                                            </strong>
                                            <strong>
                                                {{ $parametros_mensaje['nro_doc'] }}
                                            </strong>
                                        </p>
                                        <p>
                                            Internación: 
                                            <strong>
                                                {{ $parametros_mensaje['numero_internacion'] }}
                                            </strong>
                                            ,   Tipo: 
                                            <strong>
                                                {{ $parametros_mensaje['tipo_internacion'] }}
                                            </strong>
                                        </p>
                                        <p>
                                            Diagnóstico: 
                                            <strong>
                                                {{ $parametros_mensaje['codigo_diagnostico'] }} - {{ $parametros_mensaje['descripcion_diagnostico'] }}
                                            </strong>
                                        </p>
                                        @if($parametros['usuario'] != '')
                                            <p>
                                                Mensaje de {{ $parametros_mensaje['usuario'] }}: &nbsp;
                                                <em>
                                                    {{ $parametros_mensaje['mensaje']}}
                                                </em>
                                            </p>
                                        @endif
                                        @if($parametros_mensaje['url'] != '')
                                            <hr>
                                            <p>
                                                Para acceder, ingrese en la siguiente dirección: 
                                                <a href="{{ $parametros_mensaje['url'] }}">
                                                    {{ $parametros_mensaje['url'] }}
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- <div class="content">
                <table style="height: 149px; width: 537px; bgcolor=''">
                    <tbody>
                        <tr style="height: 118px;">
                            <td style="width: 527px; height: 118px;">
                                <p class="callout" 
                                    style="text-align: center;">
                                    <strong>
                                        Informaci&oacute;n de contacto
                                    </strong>
                                </p>
                                <p class="callout" 
                                    style="text-align: center;">
                                    <strong>
                                        Tel&eacute;fono: +54 9 11 2619-4543
                                        <br/> 
                                        Email:
                                        <span class="negro">
                                            <a href="mailto:casa@cajaabogados.org.ar">
                                                casa@cajaabogados.org.ar
                                            </a>
                                        </span>
                                    </strong>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="content">
                &nbsp;
                <table style="height: 119px; width: 535px;">
                    <tbody>
                        <tr>
                            <td style="width: 525px; text-align: center;">
                                &nbsp; &nbsp; &nbsp; &nbsp;
                                <div class="content">
                                    <strong>
                                        <span style="color: #999999;">
                                            <span style="color: #3366ff;">
                                                CASA
                                            </span> 
                                            CENTRO DE ATENCION INTEGRAL
                                        </span>
                                    </strong>
                                </div>
                                <div class="content">
                                    &nbsp;
                                </div>
                                <div class="content">
                                    <strong>
                                        <span class="telefono">
                                            0800-222-CASA (2272)
                                        </span>
                                    </strong>
                                </div>
                                <div class="content">
                                    <span class="negro">
                                        <span class="chico">
                                            Informaci&oacute;n las 24 hs
                                            <br/>
                                            Autorizaciones: De lunes a viernes de 8 a 17 hs.
                                        </span>
                                    </span>
                                </div>
                                <p>
                                    &nbsp;
                                    <span class="negro">
                                        E
                                    </span>
                                    <span class="negro">
                                        -mail: 
                                        <strong>
                                            <a href="mailto:autorizaciones@cajaabogados.org.ar" 
                                                target="_blank" 
                                                rel="noopener">
                                                autorizaciones@cajaabogados.org.ar
                                            </a>
                                        </strong>
                                    </span>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="content">
                &nbsp;
            </div> -->
        </td>
        <td style="width: 10px;">
            &nbsp;
        </td>
    </tr>
    </tbody>
</table>
<!-- FOOTER -->
<p>
    &nbsp;
</p>
<table class="footer-wrap" 
    style="width: 575px;">
    <tbody>
        <tr>
            <td style="width: 5px;">
                &nbsp;
            </td>
            <td class="container" 
                style="width: 547px;"><!-- content -->
                <div class="content">
                    <table style="width: 545px;">
                        <tbody>
                            <tr>
                                <td style="width: 535px; align='center'">
                                    <p>
                                        <!-- <a href="http://www.casa.org.ar/">
                                            T&eacute;rminos de uso
                                        </a>
                                         |   -->
                                        <a href="http://www.bpay.com.ar/">
                                            Propiedad intelectual
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td><!-- /content -->
            <td style="width: 10px;">
                &nbsp;
            </td>
        </tr>
    </tbody>
</table>
<!-- /FOOTER -->
<p>
    &nbsp;
</p>
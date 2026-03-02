<!-- If you delete this meta tag, the ground will open and swallow you. -->
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
                                                NOTIFICACIÓN  
                                                <span style="color: #999999;">
                                                    DE ORDEN DE COMPRA N° {{ $data['numero_oc'] }}&nbsp;-&nbsp; 
                                                    <strong>
                                                        {{ $data['nombre_afiliado'] }} &nbsp; 
                                                    </strong>
                                                    ({{ $data['numero_afiliado']}})
                                                </span>
                                            </span>
                                        </strong>
                                    </div>
                                    <div class="noticia">
                                        <p>
                                            Estimado {{ $data['razon_social'] }} 
                                            <br>
                                            Le informamos por este medio que hemos emitido una orden de compra referida a la cotización número {{ $data['nro_cotizacion'] }}
                                        </p>
                                        <p>
                                            Por favor acceda a través de este enlace a nuestra plataforma de cotizaciones con su cuit.
                                        </p>
                                        <a href="{{ $data['url_proveedor'] }}">
                                            <span class="text text-link">
                                                {{ $data['url_proveedor'] }}
                                            </span>
                                        </a>
                                        <p>
                                            En la opción Orden de Compra de la sección Compras.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="content">
                &nbsp;
            </div>
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
                                         | --> 
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
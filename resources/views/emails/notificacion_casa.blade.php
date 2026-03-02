<!-- If you delete this meta tag, the ground will open and swallow you. -->
<table style="height: 148px; width: 568px;">
    <tbody>
        <tr>
            <td style="width: 558px;">
                <h1 style="text-align: center;">
                    <span style="color: #000000;">
                        <span style="color: #3366ff;">
                            {{ $datos['header'] }}
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
                        Validación 
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
                                                EMISION 
                                                <span style="color: #999999;">
                                                    DE VALIDACION
                                                </span>
                                            </span>
                                        </strong>
                                    </div>
                                    <div class="noticia">
                                        <p style="text-align: justify;">
                                            <strong>
                                                <span style="color: #3366ff;">
                                                    {{ $datos['header'] }}
                                                </span>
                                            </strong> 
                                            le adjunta su registro de validación.
                                        </p>
                                        <p style="text-align: justify;">
                                            <strong>
                                                Importante:
                                            </strong>
                                            &nbsp;Recuerde que debe presentar el comprobante de solitud de validación en el prestador.
                                            <br/>
                                            <br />
                                            <strong>
                                                Tenga en cuenta que la falta de pago a t&eacute;rmino impide el acceso al servicio.
                                                <br />
                                            </strong>
                                        </p>
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
            </div> -->
            @if($observaciones != null || $observaciones != '')
                <div class="content">
                    <hr>
                    <strong>
                        Observaciones:
                    </strong>
                    {{ $observaciones }}
                    <hr>
                </div>
            @endif
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
                                                {{ $datos['header'] }}
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
                                            <a href="mailto:autorizacionescasa@cajaabogados.org.ar" 
                                                target="_blank" 
                                                rel="noopener">
                                                autorizacionescasa@cajaabogados.org.ar
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
                                        <a href="http://www.casa.org.ar/">
                                            T&eacute;rminos de uso
                                        </a>
                                         |  
                                        <a href="http://www.casa.org.ar/">
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
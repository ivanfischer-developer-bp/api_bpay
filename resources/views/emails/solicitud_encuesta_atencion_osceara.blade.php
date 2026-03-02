<!-- If you delete this meta tag, the ground will open and swallow you. -->
<table style="height: 148px; width: 575px;">
    <tbody>
        <tr>
            <td style="width: 575px;">
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
                <h2 style="text-align: center;">
                    <span style="color: #3366ff;">
                        Encuesta a 
                    </span> 
                    <span style="color: #999999;">
                        afiliados y pacientes
                    </span>
                </h2>
            </td>
        </tr>
        <hr>
    </tbody>
</table>
<table class="body-wrap" style="width: 554px; bgcolor=''">
    <tbody>
        <tr>
            <div class="content">
                <table>
                    <tbody>
                        <tr>
                            <td style="width: 558px;">
                                <div class="detalle-titulo" 
                                    style="text-align: center;">
                                    <h3 style="color: #3366ff;">
                                        <strong>
                                            Contanos tu opini&oacute;n
                                        </strong>
                                    </h3>
                                </div>
                                <div class="noticia">
                                    <p>
                                        Estimado/a {{ $data['nombre'] }},
                                        <br>
                                        Seg&uacute;n nuestros registros, realizaste una consulta en {{ $data['efector'] }} el 
                                        {{ $data['fecha_consulta'] }} y queremos saber c&oacute;mo fue tu experiencia.
                                        <br>
                                        Responder la encuesta solo te llevar&aacute; un minuto.
                                    </p>
                                    <div id="botonesRespuesta">
                                        <a href="{{ $data['url_encuesta'] }}" 
                                            style="
                                                display: inline-block;
                                                background-color: #007bff;
                                                color: #fff !important;
                                                text-decoration: none;
                                                padding: 12px 24px;
                                                border-radius: 24px;
                                                font-weight: bold;
                                                margin: 0 10px 10px 0;
                                                box-shadow: 0 2px 6px rgba(0,0,0,0.07);
                                                transition: background 0.2s;
                                            ">
                                            <strong>
                                                Responder Encuesta de atenci&oacute;n
                                            </strong>
                                        </a>
                                        <a href="{{ $data['url_no'] }}" 
                                            style="
                                                display: inline-block;
                                                background-color: rgba(0,123,255,0.15);
                                                color: #007bff !important;
                                                text-decoration: none;
                                                padding: 12px 24px;
                                                border-radius: 24px;
                                                font-weight: bold;
                                                margin: 0 0 10px 10px;
                                                box-shadow: 0 2px 6px rgba(0,0,0,0.04);
                                                transition: background 0.2s;
                                            ">
                                            <strong>
                                                No realic&eacute; la consulta mencionada
                                            </strong>
                                        </a>
                                    </div>
                                    <h4>
                                        <span style="color: #3366ff;">
                                            ¡Muchas Gracias!
                                        </span>
                                    </h4>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </tr>
    </tbody>
</table>
<!-- FOOTER -->
<p>
    &nbsp;
</p>
<table class="footer-wrap" style="width: 575px;">
    <tbody>
        <tr>
            <td class="container" style="width: 575px;">
                <div class="content">
                    <table style="width: 575px;">
                        <tbody>
                            <tr>
                                <td style="width: 575px; align='center'">
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
            </td>
        </tr>
    </tbody>
</table>
<!-- /FOOTER -->
<p>
    &nbsp;
</p>
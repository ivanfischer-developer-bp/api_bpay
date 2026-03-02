<table style="height: 148px; width: 568px;">
    <tbody>
        <tr>
            <td style="width: 558px;">
                <h1 style="text-align: center;">
                    <span style="color: #000000;">
                        <span style="color: #999999;">
                            SOLICITUD DE&nbsp;
                        </span>
                    </span>
                    <span style="color: #999999;">
                        SOPORTE
                    </span>
                </h1>
                <h3 style="text-align: center;">
                    <span style="color: #3366ff;">
                        Ambiente: 
                    </span> 
                    <span style="color: #999999;">
                        {{ $ambiente }}
                    </span>&nbsp;
                    <span style="color: #3366ff; font-size: 12px;">
                        (Nuevo)
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
                                    <div class="detalle-titulo" >
                                        <strong>
                                            <span style="color: #3366ff;">
                                                USUARIO: &nbsp; 
                                                <span style="color: #999999;">
                                                    {{ $usuario }}
                                                </span>
                                            </span>
                                        </strong>
                                    </div>
                                    @if(isset($email_usuario) && $email_usuario != '')
                                    <div class="detalle-titulo" >
                                        <strong>
                                            <span style="color: #3366ff;">
                                                EMAIL: &nbsp; 
                                                <span style="color: #999999;">
                                                    {{ $email_usuario }}
                                                </span>
                                            </span>
                                        </strong>
                                    </div>
                                    @endif
                                    <div class="noticia">
                                        <p>
                                            <strong>
                                                <span style="color: #3366ff;">
                                                    MENSAJE: &nbsp; 
                                                    </span>
                                            </strong>
                                            {{ $mensaje }}
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
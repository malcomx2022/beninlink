<div role="tabpanel" class="tab-pane wow zoomIn" id="purchase-code-tab">
    <div class="scroll-box">
        <h3 class="title">Pre-Installation</h3>
        <div class="section mt-2">
            <p>1. Please configure your PHP settings to match following requirements:</p>
            <hr />
            <div>
                <table width="100%">
                    <thead>
                        <tr>
                            <th width="25%">PHP Settings</th>
                            <th width="27%">Current Version</th>
                            <th>Required Version</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo $current_php_version; ?></td>
                            <td><?php echo $php_version_required; ?>+</td>
                            <td class="text-center">
                                <?php if ($php_version_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section mt-2">
            <p>2. Please make sure the extensions/settings listed below are installed/enabled:</p>
            <hr />
            <div>
                <table width="100%">
                    <thead>
                        <tr>
                            <th width="25%">Extension/settings</th>
                            <th width="27%">Current Settings</th>
                            <th>Required Settings</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>MySQLi</td>
                            <td> <?php if ($mysql_success) { ?>
                                    On
                                <?php } else { ?>
                                    Off
                                <?php } ?>
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                <?php if ($mysql_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>GD</td>
                            <td> <?php if ($gd_success) { ?>
                                    On
                                <?php } else { ?>
                                    Off
                                <?php } ?>
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                <?php if ($gd_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>cURL</td>
                            <td> <?php if ($curl_success) { ?>
                                    On
                                <?php } else { ?>
                                    Off
                                <?php } ?>
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                <?php if ($curl_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>allow_url_fopen</td>
                            <td> <?php if ($allow_url_fopen_success) { ?>
                                    On
                                <?php } else { ?>
                                    Off
                                <?php } ?>
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                <?php if ($allow_url_fopen_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>OpenSSL PHP Extension</td>
                            <td>@if( OPENSSL_VERSION_NUMBER < 0x009080bf)
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @else
                                    On
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if( OPENSSL_VERSION_NUMBER < 0x009080bf)
                                    <i class="status fa fa-times-circle-o"></i>
                                @else
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>PDO PHP Extension</td>
                            <td>@if(PDO::getAvailableDrivers())
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(PDO::getAvailableDrivers())
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>BCMath PHP Extension</td>
                            <td>@if(extension_loaded('bcmath'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('bcmath'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Ctype PHP Extension</td>
                            <td>@if(extension_loaded('ctype'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('ctype'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Fileinfo PHP Extension</td>
                            <td>@if(extension_loaded('fileinfo'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('fileinfo'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Zip PHP Extension</td>
                            <td>@if(extension_loaded('zip'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('zip'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Mbstring PHP Extension</td>
                            <td>@if(extension_loaded('mbstring'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('mbstring'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Tokenizer PHP Extension</td>
                            <td>@if(extension_loaded('tokenizer'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('tokenizer'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>XML PHP Extension</td>
                            <td>@if(extension_loaded('xml'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('xml'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>JSON PHP Extension</td>
                            <td>@if(extension_loaded('json'))
                                    On
                                @else
                                @php $all_requirement_success = false; @endphp
                                    Off
                                @endif
                            </td>
                            <td>On</td>
                            <td class="text-center">
                                @if(extension_loaded('json'))
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                @else
                                    <i class="status fa fa-times-circle-o"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>date.timezone</td>
                            <td> <?php if ($timezone_success) {
                                    echo $timezone_settings;
                                    } else {
                                        echo "Null";
                                    } ?>
                            </td>
                            <td>Timezone</td>
                            <td class="text-center">
                                <?php if ($timezone_success) { ?>
                                    <i class="status fa fa-circle-check font-20 text-success"></i>
                                <?php } else { ?>
                                    <i class="status fa fa-times-circle-o"></i>
                                <?php } ?>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

        <div class="section mt-2">
            <p>3. Please make sure you have set the <strong>writable</strong> permission on the following folders/files:</p>
            <hr />
            <div>
                <table width="100%" >
                    <tbody>
                        <?php
                        foreach ($writeable_directories as $value) {
                            ?>
                            <tr>
                                <td id="first-td" width="87%"  ><?php echo $value; ?></td>

                                <td class="text-center">
                                    <?php if (is_writeable($value)) { ?>
                                        <i class="status fa fa-circle-check font-20 text-success"></i>
                                        <?php
                                    } else {
                                        $all_requirement_success = false;
                                        ?>
                                        <i class="status fa fa-times-circle-o"></i>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between mt-5">
        <button   class="btn btn-primary previous"  type="button"><i class='fa fa-arrow-left'></i> Preview</button>
        <button   class="btn btn-primary form-next" type="button"> Next  <i class='fa fa-arrow-right'></i></button>
    </div>
</div>

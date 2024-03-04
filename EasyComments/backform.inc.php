<?php
global $fileLog;
?>


<div style="width:100%;height:auto;padding:5px;background:#000;color:#fff;line-height:1.7;margin-bottom:10px;">

    <?php

    echo @file_get_contents($fileLog) == '' ? 'Nothing here yet' : file_get_contents($fileLog); ?>



</div>


<form method="post">
    <input type="submit" name="deletelog" style="border:solid 1px;padding:5px 15px;background:#333;color:#fff;display:inline-block;border-radius:5px;text-decoration:none;" value="<?php i18n('EasyComments/CLEARLOG');?>"> 
</form>
<br><br>
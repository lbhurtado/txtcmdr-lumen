<?php

$messages_part = null;
$variables_part = null;
$instructions = array();

/*
$forwards = array(
    '09189362340' => "The your OTP is $otp.",
    '09173011987' => "The your OTP is $otp.",
);
*/

if ($reply) $messages_part[] = array('content' => $reply);

if (isset($forwards))
    foreach($forwards as $key=>$value) {
        $forward_part['content'] = $value;
        $forward_part['to_number'] = $key;
        $messages_part[] = $forward_part;
    }

/*
$variables = array(
    'state.id' => "recruiting",
    'contact.vars.recruit' => "09189362340",
);
*/

if (isset($variables))
    foreach($variables as $key=>$value){
        $variables_part[$key] = $value;
    };

if ($messages_part) $instructions ['messages'] = $messages_part;
if ($variables_part) $instructions['variables'] = $variables_part;

echo json_encode($instructions);

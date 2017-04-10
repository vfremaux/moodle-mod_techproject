/**
 *
 *
 */
// jshint unused:false, undef:false

function senddata(cmd) {
    document.forms['groupopform'].work.value = "do" + cmd;
    document.forms['groupopform'].submit();
}

function cancel() {
    document.forms['groupopform'].submit();
}

function sendgroupdata() {
    document.forms['groupopform'].submit();
}

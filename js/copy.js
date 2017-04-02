/**
 *
 *
 */
// jshint unused:false, undef:false

function senddata() {
    document.forms['copywhatform'].work.value = 'confirm';
    document.forms['copywhatform'].submit();
}

function cancel() {
    document.forms['copywhatform'].work.value = 'setup';
    document.forms['copywhatform'].submit();
}

function formControl(entity) {

    var adiv;

    switch (entity) {

        case 'requs':
            if (!document.forms['copywhatform'].requs.checked === true) {
                document.forms['copywhatform'].spectoreq.disabled = true;
                adiv = document.getElementById('spectoreq_span');
                adiv.className = 'dithered';
            } else {
                document.forms['copywhatform'].spectoreq.disabled = false;
                adiv = document.getElementById('spectoreq_span');
                adiv.className = '';
            }
            break;

        case 'specs':
            if (!document.forms['copywhatform'].specs.checked === true) {
                document.forms['copywhatform'].spectoreq.disabled = true;
                document.forms['copywhatform'].tasktospec.disabled = true;
                adiv = document.getElementById('tasktospec_span');
                adiv.className = 'dithered';
                adiv = document.getElementById('spectoreq_span');
                adiv.className = 'dithered';
            } else {
                document.forms['copywhatform'].spectoreq.disabled = false;
                document.forms['copywhatform'].tasktospec.disabled = false;
                adiv = document.getElementById('tasktospec_span');
                adiv.className = '';
                adiv = document.getElementById('spectoreq_span');
                adiv.className = '';
            }
            break;

        case 'tasks':
            if (!document.forms['copywhatform'].tasks.checked === true) {
                document.forms['copywhatform'].tasktospec.disabled = true;
                document.forms['copywhatform'].tasktodeliv.disabled = true;
                document.forms['copywhatform'].tasktotask.disabled = true;
                adiv = document.getElementById('tasktospec_span');
                adiv.className = 'dithered';
                adiv = document.getElementById('tasktotask_span');
                adiv.className = 'dithered';
                adiv = document.getElementById('tasktodeliv_span');
                adiv.className = 'dithered';
            } else {
                document.forms['copywhatform'].tasktospec.disabled = false;
                document.forms['copywhatform'].tasktodeliv.disabled = false;
                document.forms['copywhatform'].tasktotask.disabled = false;
                adiv = document.getElementById('tasktospec_span');
                adiv.className = '';
                adiv = document.getElementById('tasktotask_span');
                adiv.className = '';
                adiv = document.getElementById('tasktodeliv_span');
                adiv.className = '';
            }
            break;

        case 'deliv':
            if (!document.forms['copywhatform'].deliv.checked === true) {
                document.forms['copywhatform'].tasktodeliv.disabled = true;
                adiv = document.getElementById('tasktodeliv_span');
                adiv.className = 'dithered';
            } else {
                document.forms['copywhatform'].tasktodeliv.disabled = false;
                adiv = document.getElementById('tasktodeliv_span');
                adiv.className = '';
            }
            break;
    }
}

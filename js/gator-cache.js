jQuery(document).ready(function($){
    if(0 === $("#gc_settings").length){
        return;
    }
    if('undefined' !== typeof($.ui.tabs)){
        $("#gc_load").hide();
        $("#gci_tabs").show();
        $("#gci_tabs").tabs();
        $("#gci_roles,#post_types").chosen({no_results_text: "Oops, nothing found!"});
    }
});

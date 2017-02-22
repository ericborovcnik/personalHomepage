Ext.namespace('Ext.ux.plugins');
 
/**
 * Ext.ux.plugins.TitleToolbar plugin for Ext.Panel
 *
 * @author  Ing. Aaron Snyder
 * @date    March 3, 2010
 *
 * @class Ext.ux.plugins.TitleToolbar
 * @extends Ext.util.Observable
 */
 
Ext.ux.plugins.TitleToolbar = function(config) {
    Ext.apply(this, config);
};

// plugin code
Ext.extend(Ext.ux.plugins.TitleToolbar, Ext.util.Observable, {
    init:function(panel) {
        var thisPlugin = this;
        Ext.apply(panel,{
            onRender: panel.onRender.createSequence(function(ct,position) {
                var toolbarDiv = Ext.id();
                var titleToolbarObject = Ext.DomHelper.insertFirst(this.header.dom, '<div id="'+toolbarDiv+'" class="x-tab-toolbar-wrap" style="position:absolute;top:0px;right:0px;width:'+(thisPlugin.width || 150)+'px;background:none;float:right;overflow:hidden;border:0px;margin-left:0px;"></div>');

                thisPlugin.tbar.render(toolbarDiv);
                if (thisPlugin.pushTitle || Ext.isIE) {
                    var titleText = Ext.query('span[class^=x-panel-header-text]',this.header.dom);
                    titleText[0].style.position = 'relative';
                    titleText[0].style.top = ((thisPlugin.pushDown) ? thisPlugin.pushDown:5) +'px';
                }
                thisPlugin.tbar.el.dom.style.border='0px';
                thisPlugin.tbar.el.dom.style.background='none';
            })//);
        });
    } // end of function init
}); // end of plugin
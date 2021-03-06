/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['underscore'], function (_) {
    'use strict';

    var baseUrls, sections;

    var canonize = function(url){
        var route = url;
        for (var key in baseUrls) {
            route = url.replace(baseUrls[key], '');
            if (route != url) {
                break;
            }
        }
        return route.replace(/^\/?index.php\/?/, '').toLowerCase();
    };

    return {
        getAffectedSections: function (url) {
            var route = canonize(url);
            var actions = _.find(sections, function(val, section){
                return (route.indexOf(section) === 0);
            });

            return _.union(_.toArray(actions), _.toArray(sections['*']));
        },
        'Magento_Customer/js/section-config': function(options) {
            baseUrls = options.baseUrls;
            sections = options.sections;
        }
    };
});

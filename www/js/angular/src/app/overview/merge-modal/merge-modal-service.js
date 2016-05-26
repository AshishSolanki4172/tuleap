angular
    .module('tuleap.pull-request')
    .service('MergeModalService', MergeModalService);

MergeModalService.$inject = [
    '$modal',
    'lodash'
];

function MergeModalService(
    $modal,
    lodash
) {
    var self = this;

    lodash.extend(self, {
        showMergeModal: showMergeModal
    });

    function showMergeModal() {
        var modalInstance = $modal.open({
            templateUrl: 'overview/merge-modal/merge-modal.tpl.html',
            controller : 'MergeModalController as merge_modal'
        });
        return modalInstance.result;
    }
}

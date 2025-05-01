<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="affiliates-portal-widget" data-is-self="<?php echo ( $is_self ? '1' : '0' ); ?>">
                <h3 class="mb-4">Job Listings</h3>
                <div id="affiliates-job-list"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteJobModal" tabindex="-1" role="dialog" aria-labelledby="deleteJobModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
         <h5 class="modal-title" id="deleteJobModalLabel">Confirm Delete</h5>
         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
             <span aria-hidden="true">&times;</span>
         </button>
      </div>
      <div class="modal-body">
         Are you sure you want to delete this job?
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
         <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
      </div>
    </div>
  </div>
</div>
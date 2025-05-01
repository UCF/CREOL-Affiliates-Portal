<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="affiliates-create-job-widget">
                <h3 class="mb-4">Create a Job</h3>
                <form id="affiliates-create-job-form">
                    <label for="job-title">Job Title:</label>
                    <br/>
                    <input class="form-control mb-3" type="text" id="job-title" name="title" required/>
                    
                    <label for="job-description">Job Description:</label>
                    <br/>
                    <textarea style="height: 25vh;" class="form-control mb-3" id="job-description" name="description" required></textarea>
                    
                    <label for="job-contact">Contact Email:</label>
                    <br/>
                    <input class="form-control mb-4" type="email" id="job-contact" name="contact" required/>
                    
                    <input class="w-100 btn btn-primary mb-3" type="submit" value="Create Job"/>
                </form>
                <div id="job-response"></div>
            </div>
        </div>
    </div>
</div>
<?php
@if (merchant()->kyc_status == 0)
<div class="col-12 mb-3">
  <div class="p-2 rounded-3 pending">
    <h6 class="text-dark text-center"><i class="fas fa-exclamation-triangle me-2"></i> @lang('Please submit your KYC information to enjoy full access ! ') <a href="{{route('merchant.kyc.form')}}" class="text-primary">@lang('Take me there')</a></h6>
  </div>
</div>
@elseif(merchant()->kyc_status == 2)
<div class="col-12 mb-3">
  <div class="p-2 rounded-3 review">
    <h6 class="text-dark text-center"><i class="fas fa-search-location"></i> @lang('Your KYC data is currently under review.')</h6>
  </div>
</div>
@elseif(merchant()->kyc_status == 3)
<div class="col-12 mb-3">
  <div class="p-2 rounded-3 rejected">
    <h6 class="text-dark text-center"><i class="fas fa-exclamation-circle"></i> @lang('Your KYC data was rejected. Please re-submit your data.')  <a href="{{route('merchant.kyc.form')}}" class="text-primary">@lang('Take me there.')</a> <a href="javascript:void(0)" class="text-warning ms-5 reason"  data-reason="{{merchant()->kyc_reject_reason}}"><i class="fas fa-info-circle"></i> @lang('See Reasons')</a></h6>
  </div>

  <div class="modal modal-blur fade" id="modal-reason" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-dark">@lang('KYC data reject reasons.')</h5>
        
        </div>
        <div class="modal-body">
          <textarea disabled  rows="5" class="form-control reason-text"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-dark ms-auto text-white" data-dismiss="modal">@lang('Close')</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
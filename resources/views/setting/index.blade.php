@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
@endsection

@section('content')
<div class="row">
	<div class="col-md-12">
		<div class="card mb-3" style="margin: 18px 0px;">
			<div class="card-header">
				<div class="row flex-between-end">
					<div class="col-auto align-self-center">
						<h5 class="mb-0" data-anchor="data-anchor" id="table-example">Application Setting :: {{ $type }}
						</h5>
					</div>
					<div class="col-auto ms-auto">
						<div class="nav nav-pills nav-pills-falcon">
							<a href="{{ route('dashboard')  }}" class="btn btn-outline-secondary">
								<i class="fa fa-arrow-left me-1"></i> Go Back
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="card-body">
				<form class="form-sample" id="settingForm{{ $setting_id }}"
					action="{{ route('setting', ['id' => $setting_id])  }}" method="post" enctype="multipart/form-data">
					@csrf
					<fieldset @if(Helper::userCan(101, 'can_edit' )==false) disabled @endif>
						<div class="row">
							@foreach($setting as $field)
							@if($field['filed_type'] == 'file')
							<div class="col-md-6 mb-3">
								<div class="mb-2">
									<img src="{{ asset('storage/' . $field['filed_value']) }}" alt="..."
										class="img-thumbnail custom-img">
								</div>
								<label class="form-label" for="{{ $field['setting_name'] }}">
									{{ $field['filed_label'] }}
								</label>
								<input class="form-control" id="{{ $field['setting_name'] }}" placeholder="Upload Image"
									type="{{ $field['filed_type'] }}" name="{{ $field['setting_name'] }}" />

								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@elseif($field['filed_type'] == 'number')
							<div class="col-md-6 mb-3">
								<label class="form-label d-block" for="{{ $field['setting_name'] }}">
									{{ $field['filed_label'] }}
								</label>
								<input class="form-control" step="0.1" type="number" name="{{ $field['setting_name'] }}"
									value="{{ $field['filed_value'] }}">
								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@elseif($field['filed_type'] == 'check')
							<div class="col-md-6 mb-3">
								<label class="form-label d-block" for="{{ $field['setting_name'] }}">
									{{ $field['filed_label'] }}
								</label>
								<div class="custom-switch py-2">
									<label class="switch">
										<input type="checkbox" name="{{ $field['setting_name'] }}" value="1"
											@checked($field['filed_value']==1)>
										<span class="slider round"></span>
									</label>
								</div>
								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@elseif($field['filed_type'] == 'textarea')
							<div class="col-md-6 mb-3">
								<label class="form-label d-block" for="{{ $field['setting_name'] }}">
									{{ $field['filed_label'] }}
								</label>
								<textarea class="form-control" id="{{ $field['setting_name'] }}"
									name="{{ $field['setting_name'] }}">{{ $field['filed_value'] }}</textarea>
								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@elseif($field['filed_type'] == 'textarea-html')
							<div class="col-md-12 mb-3">
								<label class="form-label d-block" for="{{ $field['setting_name'] }}">
									{{ $field['filed_label'] }}
								</label>
								<textarea class="form-control summernote" id="{{ $field['setting_name'] }}"
									name="{{ $field['setting_name'] }}">{{ $field['filed_value'] }}</textarea>
								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@else
							<div class="col-md-6 mb-3">
								<label class="form-label" for="{{ $field['setting_name'] }}">{{ $field['filed_label']
									}}</label>
								<input class="form-control" name="{{ $field['setting_name'] }}"
									id="{{ $field['setting_name'] }}" type="{{ $field['filed_type'] }}"
									value="{{ $field['filed_value'] }}" placeholder="{{ $field['filed_label'] }}" />
								@error($field['setting_name'])
								<span class="invalid-feedback" role="alert">
									<span class="text-danger">{{ $message }}</span>
								</span>
								@enderror
							</div>
							@endif
							@endforeach
							<div class="col-md-12">
								@if(Helper::userCan(101, 'can_edit'))
								<button id="submit" type="submit"
									class="btn btn-secondary me-2 btn-custom btn-custom-color">Submit</button>
								@endif
								<a href="{{  route('dashboard') }}" class="btn btn-light btn-custom">Cancel</a>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/plugins/summernote/summernote.min.js') }}"></script>

<script type="text/javascript">

	$(document).ready(function () {
		$('.summernote').summernote({
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'underline', 'clear']],
				['fontname', ['fontname']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link', 'picture']],
				['view', ['codeview', 'help']],
			],
			height: 200,
			callbacks: {
				onImageUpload: function (files) {
					sendFile(files[0], this);
				}
			}
		});

		function sendFile(file, selector) {
			data = new FormData();
			data.append("file", file);
			$.ajax({
				data: data,
				type: "POST",
				url: "{{ route('upload_image') }}",
				cache: false,
				contentType: false,
				processData: false,
				success: function (url) {
					var image = $('<img>').attr('src', url);
					$(selector).summernote("insertNode", image[0]);
				}
			});
		}

		let buttons = $('.note-editor button[data-toggle="dropdown"]');
		buttons.each((key, value) => {
			$(value).on('click', function (e) {
				$(this).attr('data-bs-toggle', 'dropdown')

			})
		})

		$("#settingForm1").validate({
			rules: {
				application_name: {
					required: true,
					minlength: 2,
					maxlength: 50,
				},
				copyright: {
					required: true,
					minlength: 2,
					maxlength: 150,
				},
				address: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
                secondaddress: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				email: {
					required: true,
					email: true
				},
				phone: "required",
				favicon: {
					filesize: 0.1,
					extension: "jpg,jpeg,png,svg,gif"
				},
				logo: {
					filesize: 0.5,
					extension: "jpg,jpeg,png,svg,gif"
				},
				maesage: "required",
			},
			messages: {
				application_name: {
					required: "Please enter Application name",
					minlength: "Application Name must consist of at least 2 characters.",
					maxlength: "Application Name must not exceed characters limit 50.",
				},
				copyright: {
					required: "Please enter copyright information",
					minlength: "copyright information must consist of at least 2 characters.",
					maxlength: "copyright information must not exceed characters limit 50.",
				},
				address: {
					required: "Please enter Address",
					minlength: "Address must consist of at least 2 characters.",
					maxlength: "Address must not exceed characters limit 50.",
				},
                secondaddress: {
					required: "Please enter Address",
					minlength: "Address must consist of at least 2 characters.",
					maxlength: "Address must not exceed characters limit 50.",
				},
				email: {
					required: "Please enter your email address",
					email: "Please enter a valid email address"
				},
				phone: "Please enter Phone number",
				maesage: "Please enter Message",
				favicon: {
					filesize: "File size Not exceed limit 100 kb",
					extension: "You're only allowed to upload jpg,jpeg,png,gif or svg images."
				},
				logo: {
					filesize: "File size Not exceed limit 500 kb",
					extension: "You're only allowed to upload jpg,jpeg,png,gif or svg images."
				}
			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			},
			highlight: function (element, errorClass) {
				$(element).parent().addClass('has-danger')
				$(element).siblings().find('.file-upload-info').addClass('form-control-danger')
				$(element).siblings().find('span').children().first().addClass('btn-danger')
			}
		});


		$("#settingForm2").validate({
			rules: {
				facebook: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				twitter: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				linkdin: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				instagram: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
			},
			messages: {
				facebook: {
					required: "Please enter Facebook link",
					minlength: "Link must consist of at least 2 characters.",
					maxlength: "Link must not exceed characters limit 100.",
				},
				twitter: {
					required: "Please enter Twitter link",
					minlength: "Link must consist of at least 2 characters.",
					maxlength: "Link must not exceed characters limit 100.",
				},
				linkdin: {
					required: "Please enter Linkdin link",
					minlength: "Link must consist of at least 2 characters.",
					maxlength: "Link must not exceed characters limit 100.",
				},
				instagram: {
					required: "Please enter Instagram link",
					minlength: "Link must consist of at least 2 characters.",
					maxlength: "Link must not exceed characters limit 100.",
				},

			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			},
		});

		$("#settingForm3").validate({
			rules: {
				email_from: {
					required: true,
					minlength: 2,
					email: true,
					maxlength: 100,
				},
				smtp_host: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				smtp_port: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				smtp_user: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				smtp_pass: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
			},
			messages: {
				email_from: {
					required: "Please enter Email From",
				},
				smtp_host: {
					required: "Please enter SMTP Host.",
				},
				smtp_port: {
					required: "Please enter SMTO post",
				},
				smtp_user: {
					required: "Please enter SMTP User.",
				},
				smtp_pass: {
					required: "Please enter SMTP password.",
				},
			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			}
		});

		$("#settingForm4").validate({
			rules: {
				razorpay_key: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				razorpay_secret: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				merchant_id: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
			},
			messages: {
				razorpay_key: {
					required: "Please enter Razorpay Key",
				},
				razorpay_secret: {
					required: "Please enter Razorpay Secret.",
				},
				merchant_id: {
					required: "Please enter Merchant Id",
				},
			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			}
		});

		$("#settingForm5").validate({
			ignore: [],
			rules: {
				textlocal_key: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				textlocal_url: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				textlocal_hash: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
				textlocal_sender: {
					required: true,
					minlength: 2,
					maxlength: 100,
				},
			},
			messages: {
				textlocal_key: {
					required: "Please enter TextLocal Key",
				},
				textlocal_url: {
					required: "Please enter TextLocal URL",
				},
				textlocal_hash: {
					required: "Please enter TextLocal Hash",
				},
				textlocal_sender: {
					required: "Please enter TextLocal Sender Id.",
				},
			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			}
		});

		$("#settingForm6").validate({
			ignore: [],
			rules: {
				load_money_qr_code: {
					required: false,
				},
				notify_modal_content: {
					required: true,
					minlength: 5,
					maxlength: 5000,
				},
			},
			messages: {
				notify_modal_content: {
					required: "Please enter notify modal content.",
				},
			},
			errorPlacement: function (label, element) {
				label.addClass('fs--1 text-danger');
				label.insertAfter(element);
			}
		});
	});
</script>
@endsection

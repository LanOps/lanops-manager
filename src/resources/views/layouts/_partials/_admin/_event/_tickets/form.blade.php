@if ($errors->any())
  	<div class="alert alert-danger">
        <ul>
          	@foreach ($errors->all() as $error)
            	<li>{{ $error }}</li>
          	@endforeach
        </ul>
  	</div>
@endif
<div class="row">
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('Ticket Name', 'name') }}
		{{ 
			html()->text('name', (@$empty ? null : @$ticket->name))->class('form-control')
		}}
	</div> 
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('Ticket Price', 'price') }}
		<div class="input-group">
			<div class="input-group-addon">{{ config('app.currency_symbol') }}</div>
				@if (isset($priceLock) && $priceLock)
					{{ 
						html()->text('price', (@$empty ? null : @$ticket->price))->class('form-control')->disabled()
					}}
				@else 
					{{ 
						html()->text('price', (@$empty ? null : @$ticket->price))->class('form-control')
					}}
				@endif
			<div class="input-group-addon">.00</div>
		</div>
	</div>

</div>
<div class="row">

	<div class="form-group col-md-6 col-sm-6 col-xs-12">
		{{ html()->label('Quantity', 'quantity') }}
		{{ 
			html()->text('quantity', (@$empty ? null : @$ticket->quantity))->class('form-control')
		}}
		<small>If unlimited, leave blank</small>
	</div>  
	<div class="form-group col-md-6 col-sm-6 col-xs-12">
		{{ html()->label('Ticket Type', 'type') }}
		{{ html()->select('type', array('participant' => 'Participant', 'spectator' => 'Spectator'), @$ticket->type)->class('form-control') }}
	</div> 
	<div class="form-group col-xs-12">
		<div class="checkbox">
			<label>
				@if (@$ticket->seatable || @$empty)
					{{ html()->checkbox('seatable', true, 1) }}
				@else
					{{ html()->checkbox('seatable', false, 1) }}
				@endif
				<strong>Seatable</strong>
			</label>
		</div>
	</div> 

</div>
<hr>
<h3>Purchase Period</h3>
<div class="row">
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('Start Date', 'sale_start_date') }}
		@if (@$empty)
			{{ html()->text('sale_start_date')->class('form-control') 
			}}
		@else
			{{ html()->text('sale_start_date', (@$ticket->sale_start ? date('d-m-Y', strtotime($ticket->sale_start)) : null))->class('form-control') 
			}}
		@endif
		<small>If no start leave blank</small>
	</div> 
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('Start Time', 'sale_start_time') }}
		@if (@$empty)
			{{ 
				html()->text('sale_start_time')->class('form-control')
			}}
		@else
			{{ 
				html()->text('sale_start_time', (@$ticket->sale_start ? date('H:i', strtotime($ticket->sale_start)) : null))->class('form-control')
			}}
		@endif
		<small>If no start leave blank</small>
	</div>
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('End Date', 'sale_end_date') }}
		@if(@$empty)
			{{ 
				html()->text('sale_end_date')->class('form-control')
			}}
		@else
			{{ 
				html()->text('sale_end_date', (@$ticket->sale_end ? date('d-m-Y', strtotime($ticket->sale_end)) : null))->class('form-control')
			}}
		@endif
		<small>If no end leave blank</small>
	</div> 
	<div class="form-group col-sm-6 col-xs-12">
		{{ html()->label('End Time', 'sale_end_time') }}
		@if (@$empty)
			{{ 
				html()->text('sale_end_time')->class('form-control')
			}}
		@else
			{{ 
				html()->text('sale_end_time', (@$ticket->sale_end ? date('H:i', strtotime($ticket->sale_end)) : null))->class('form-control')
			}}
		@endif
		<small>If no end leave blank</small>
	</div>

</div>
<hr>
<button type="submit" class="btn btn-default btn-success btn-block">Submit</button>

<!-- JavaScript-->
<script type="text/javascript">
	$( function() {
		$( "#sale_start_date" ).datepicker();
		$( "#sale_end_date" ).datepicker();
	});
</script>

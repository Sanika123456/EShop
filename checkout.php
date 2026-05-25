<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php 

$total = 0;
    $qry = $conn->query("SELECT c.*,p.name,i.price,p.id as pid from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id = ".$_settings->userdata('id'));
    while($row= $qry->fetch_assoc()):
        $total += $row['price'] * $row['quantity'];
    endwhile;
    if($total <= 0){
    echo "<script>alert('Cart is Empty'); location.replace('./?p=products');</script>";
    exit;
}
?>
<section class="py-5">
    <div class="container">
        <div class="card rounded-0">
            <div class="card-body"></div>
            <h3 class="text-center"><b>Checkout</b></h3>
            <hr class="border-dark">
            <form action="" id="place_order">
                <input type="hidden" name="amount" value="<?php echo $total ?>">
                <input type="hidden" name="payment_method" value="cod">
                <input type="hidden" name="paid" value="0">
                <div class="row row-col-1 justify-content-center">
                    <div class="col-6">
                        <div class="form-group col address-holder">
                            <label for="" class="control-label">Delivery Address</label>
                            <textarea id="delivery_address" cols="30" rows="3" name="delivery_address" class="form-control" required style="resize:none"><?php echo $_settings->userdata('default_delivery_address') ?></textarea>
                        </div>
                        <div class="col">
                            <span><h4><b>Total:</b> <?php echo number_format($total) ?></h4></span>
                        </div>
                        <hr>
                        <div class="col my-3">
                        <h4 class="text-muted">Payment Method</h4>
                            <div class="d-flex w-100 justify-content-between">
                                <button type="submit" class="btn btn-flat btn-dark">Cash on Delivery</button>
                                <button type="button" id="rzp-button" class="btn btn-primary">Pay Online</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
    /*
paypal.Button.render({
    env: 'sandbox', // change for production if app is live,
 
        //app's client id's
	client: {
        sandbox:    'ARi2rJK5gQ-4catdPxRnfLOVQRMiCvq-qXMmlbItbjxWZfyq5u51mqE7Ppka7OG_0Bq5l_TIQkm45sUV',
        //production: 'AaBHKJFEej4V6yaArjzSx9cuf-UYesQYKqynQVCdBlKuZKawDDzFyuQdidPOBSGEhWaNQnnvfzuFB9SM'
    },
 
    commit: true, // Show a 'Pay Now' button
 
    style: {
    	color: 'blue',
    	size: 'small'
    },
 
    payment: function(data, actions) {
        return actions.payment.create({
            payment: {
                transactions: [
                    {
                    	//total purchase
                        amount: { 
                        	total: '<?php echo $total; ?>', 
                        	currency: 'PHP' 
                        }
                    }
                ]
            }
        });
    },
 
    onAuthorize: function(data, actions) {
        return actions.payment.execute().then(function(payment) {
    		// //sweetalert for successful transaction
    		// swal('Thank you!', 'Paypal purchase successful.', 'success');
            payment_online()
        });
    },
 
}, '#paypal-button');
function payment_online(){
    $('[name="payment_method"]').val("Online Payment")
    $('[name="paid"]').val(1)
    $('#place_order').submit()
}
$(function(){
    $('[name="order_type"]').change(function(){
        if($(this).val() ==2){
            $('.address-holder').hide('slow')
        }else{
            $('.address-holder').show('slow')
        }
    })
    $('#place_order').submit(function(e){
        e.preventDefault()
        if($('[name="delivery_address"]').val().trim()===''){ alert_toast('Delivery address is required','error'); return false; }
        start_loader();
        $.ajax({
            url:'classes/Master.php?f=place_order',
            method:'POST',
            data:$(this).serialize(),
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("an error occured","error")
                end_loader();
            },
            success:function(resp){
                if(!!resp.status && resp.status == 'success'){
                    location.replace('./')
                }else{
                    console.log(resp)
                    alert_toast("an error occured","error")
                    end_loader();
                }
            }
        })
    })
})
*/
$('#rzp-button').click(function(e){
    e.preventDefault();

    let address = $('#delivery_address').val().trim();
    let total = <?php echo $total; ?>;

    if(address === ''){
        alert_toast('Delivery address is required','error');
        return false;
    }

    if(total <= 0){
        alert_toast('Cart is empty','error');
        return false;
    }

    $(this).prop('disabled', true);

    var options = {
        "key": "rzp_test_SozrP56UdkmaET",
        "amount": total * 100,
        "currency": "INR",
        "name": "College POS",
        "description": "Order Payment",

        "handler": function (response){

            $('[name="payment_method"]').val("Online Payment");
            $('[name="paid"]').val(1);

            $.ajax({
                url:'classes/Master.php?f=place_order',
                method:'POST',
                data:$('#place_order').serialize(),
                dataType:'json',
                success:function(resp){
                    if(resp.status == 'success'){
                        location.replace('./');
                    }else{
                        alert_toast("Order failed",'error');
                        $('#rzp-button').prop('disabled', false);
                    }
                }
            });
        }
    };

    var rzp1 = new Razorpay(options);

    rzp1.on('payment.failed', function (){
        alert_toast("Payment Failed",'error');
        $('#rzp-button').prop('disabled', false);
    });

    rzp1.open();
});
$(function(){

    $('#place_order').submit(function(e){
        e.preventDefault();

        let total = <?php echo $total; ?>;

        if(total <= 0){
            alert_toast('Cart is empty','error');
            return false;
        }

        if($('#delivery_address').val().trim()===''){
            alert_toast('Delivery address is required','error');
            return false;
        }

        start_loader();

        $.ajax({
            url:'classes/Master.php?f=place_order',
            method:'POST',
            data:$(this).serialize(),
            dataType:"json",

            success:function(resp){
                if(resp.status == 'success'){
                    location.replace('./');
                }else{
                    alert_toast("Order failed","error");
                    end_loader();
                }
            },

            error:function(){
                alert_toast("An error occurred","error");
                end_loader();
            }
        });
    });

});
</script>
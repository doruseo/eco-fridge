<?php get_header(); ?>

			<div id="content">
			
				<div id="inner-content" class="wrap cf">

					<div id="main" class="col12 cf" role="main">
					
						<header class="article-header">
						
							<?php if ( has_term( 'graded-units', 'range' ) ) { ?>
								
								<div class="back-to-index products">
	
									<a href="https://www.eco-fridge.co.uk/graded-units/">Back to Second Hand index</a>
									
								</div>
							
							<?php } else { ?>
							
								<div class="back-to-index products">
	
									<?php // get all product cats/taxonomies for the current post
									$categories = get_the_terms( get_the_ID(), 'range' );
									
									
									// wrapper to hide any errors from top level categories or products without category
									if ( $categories && ! is_wp_error( $category ) ) : 
									
									    // loop through each cat
									    foreach($categories as $category) :
									      // get the children (if any) of the current cat
									      $children = get_categories( array ('taxonomy' => 'range', 'parent' => $category->term_id ));
									      $term_link = get_term_link( $category, 'range' );
									
									      if ( count($children) == 0 ) {
									          // if no children, then echo the category name.
									          echo '<a href="' . $term_link . '">'. 'Back to ' . $category->name . ' index' . '</a>';
									      }
									    endforeach;
									
									endif; ?>
									
									<?php wp_reset_query(); ?>
									
								</div>
							
							<?php } ?>
						
							
						
							<!--<h1 class="entry-title single-title" itemprop="headline"><?php the_title(); ?></h1>-->
						
						</header> <?php // end article header ?>
						
						<section class="product-wrapper entry-content cf" itemprop="articleBody">
							
							<?php woocommerce_content(); ?>
							
							<div class="spec-table-wrapper">
							
							<p>Trade prices. <strong>Free</strong> delivery into your store if possible (T&amp;C apply).<br>Bank transfer estimated 5 to 8 working days from cleared payment (T&amp;C apply).<br>Card payments estimated 8 to 10 working days from cleared payment (T&amp;C apply).<br>Delivery times above York, Leeds &amp; Blackpool can take approximately 2 to 4 Weeks (T&amp;C apply).</p>
								
								<div class="spec-table"><?php echo (types_render_field("spec-table", array( ))); ?></div>
								
								<div class="related-products-footer"><?php echo do_shortcode("[woocommerce_product_upsells]"); ?></div>
								
								<div class="spec-table-notes" style="margin: 0 0 2em 0;">
									<p>All cabinets above come with free delivery into your store if possible (t&c apply). Deliveries above York, Leeds, Blackpool &amp; outside UK mainland will encur an extra charge.</p>
									<p>Removal & Disposal of old equal size unit = £150 +VAT (t&c apply)</p>
									<p>Restocking charge of £600 +VAT for cancelled orders</p>
									<p>Finance Available (Kennet Leasing) - Please call for details </p> 
									<p>1 Year Parts &amp; Labour Warranty available at extra cost - Please call</p>
									<p>Noise level 68dBA one metre from cabinet</p>
									<p>All cabinets will become curb side delivery if there are too many steps, barriers or small doors, it will be the customers responsibility from this point onwards</p>
									<p>In the interest of continual product development we reserve the right to change any of the details and specification without notice</p>
									<p>We will terminate any Warranty/Guarantee if our staff are subjected to physical or verbal abuse in the course of carrying out their duties</p>
									<p>Every sale is considered a Business to Business Contract, so a personal purchase will not be covered under the Consumer Rights Act</p>
									<p>Train Stations, Shopping centres and out of working hour deliveries may incur a charge of £300 +VAT</p>
									<p>Before purchasing, please check the <a href="https://www.eco-fridge.co.uk/wp-content/uploads/Manual.pdf" target="_blank">user manual</a> to ensure the fridge is suitable for your store environment</p>
								</div>
								
								<p style="font-size: 0.6em;">TERMS &amp; CONDITIONS
								1.ORDERS:Buyer and Supplier, no orders submitted by the Buyer shall be deemed to be accepted by the Seller unless and until payment is received in full. The Buyer responsibility to ensure that the equipment will be fit for the purpose for which the Buyer intend to use the equipment. By submitting an order the customer agrees to apply the terms and condition of this agreement in priority over any other agreement. This agreement is the only form of contract between the parties and any customer terms and condition will not apply. Every sale is considered a business to business contract, So a personal purchase will not be covered under the consumer rights act. 2.PRICE: the Company reserves the right to amend prices at any time consequent upon increased costs of raw materials, labour, transport, market conditions or for any other reason whatsoever. 3. WEEE Directive. The Buyer will take responsibility for the finance of future collection and disposal of the cabinet, in accordance with the regulations. If the producer retains that responsibility then an extra charge will be incurred. The cost will be negotiated and invoiced separately. 4.DELIVERY/COLLECTION: dates are given as estimates only and the Company cannot accept liability for any delays howsoever caused. All cabinets are delivered to the front door of ground floor locations only. The delivery person/driver may at his discretion assist with delivering the cabinets to a location within the premises nominated by the customer, at the customer’s sole risk. 5.INSTALLATION: it is the Buyer’s responsibility to ensure that the equipment is installed and commissioned by a qualified refrigeration engineer and all costs will be borne by the Buyer. Do not install where maximum room temperature is more than 25°C and humidity over 60%. Install as described in here and the manufacturer manual. The refrigerated cabinet air temperature -1°C to +7°C. Freezer -18°C to -22°C. 6.TERMS OF PAYMENT: unless otherwise agreed by the Company in writing, payment for goods is strictly on a pro forma basis and the goods will not be reserved until payment is received. 7.TITLE TO GOODS: The risk in the goods shall however pass to the Buyer at the time of delivery. 8.CHECKING GOODS/CLAIMS FOR DAMAGE IN TRANSIT: it is the Buyer’s responsibility to inspect the goods at the time of taking delivery/collection. Should equipment arrive damaged/missing parts, the delivery/part delivery must be refused. If signed for “unexamined/examined” the Buyer/Person shall be deemed to have accepted the goods and no claim for damage/missing parts will be entertained by the Seller. Refrigerators noise level 68dBA one metre from cabinet. 9.CANCELLATION/RETURN OF GOODS: requests for return of goods for whatever reason will not be entertained by the Seller. Defects in quality or dimension of any delivery shall not be grounds for cancellation of the remainder of the contract. 10.WARRANTY: All goods are sold without Warranty. Different warranty requirements may be negotiated on a market and country basis. We will terminate any Warranty/Guarantee if our staff are subjected to physical or verbal abuse in the course of carrying out their duties. It is the Buyer’s legal responsibility to ensure that preventative maintenance is carried out and all electrical and mechanical parts tested every three months by a qualified refrigeration engineer as described in here and the manufacturer manual. Failure to provide satisfactory maintenance will invalidate any Warranty. All goods are sold with NO Warranty. Any Warranty will exclude any glass, light fittings, paintwork/rust, condensation, cosmetic damage or customer mis-use. The Seller shall be under no liability in respect of any defect arising from fair wear and tear: abnormal site conditions or failure to have the equipment serviced as described in here and the manufacturer manual. The Company shall not accept any claim for any loss of profit or economic loss resulting from defective parts. All parts must be paid for in full prior to despatch. Labour and transport costs for replacing faulty parts will be charged at the current rate. Following replacement of a part, the Company cannot guarantee that other parts will not fail in the future. The refrigerated cabinets must be maintained by a qualified refrigeration engineer every three months as described above and the manufacturer manual. Install where maximum room temperature is not over 25°C, and Relative humidity not over 60%. 11.VARIANCE: All dimensions given are to be regarded as being for guidance only and are not binding on the Company. It is the responsibility of the Buyer to allow sufficient clearance for installation: and to ensure that the ventilation grill panels are kept free for adequate air circulation.: also to ensure that cabinets are positioned away from direct sunlight or source of hot or cold air. Cabinet noise level 68dBA 12.MODIFICATIONS: if any alterations have been undertaken by the buyer or a contractor the Company shall not be held responsible for any defects or problems which may occur: any such alterations will invalidate the warranty on the equipment. All costs incurred by the Supplier to diagnose problems arising from such modifications will be borne by the Buyer.</p>
								
							</div>
							
							
							
						</section> <?php // end article section ?>

					</div>

				</div>

			</div>

<?php get_footer(); ?>

<!-- vuejs main app -->
<div id="wpfa-app-initial-wizard" class="vue-app-wpfa">
	<div class="container-wpfa-main-app">

		<div class="header-app"> 
			<img class="logo-wp-frontend-admin" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/imgs/logo-wpfa.svg' ); ?>" alt="">
			<p class="title-section">{{ languageApp.wpfaTemplates }}</p>
		</div>

		<div v-if="!wpfaAppStarted" class="animation-star-app-wpfa">
			<div class="container-animation-loading-menu-items">
				<div class="lds-ellipsis">
					<div></div>
					<div></div>
					<div></div>
					<div></div>
				</div>
			</div>
		</div>

		<div v-if="wpfaAppStarted" class="wpfa-body-steps">

			<!-- Menu section for steps -->
			<div class="wpfa-menu-steps">
				<ul class="list-vertical-steps">
					<li 
						class="item-menu-vertical-step"
						v-for="menuItem in menuForInitialConfiguration"
						:class="{
							'active-step-wizard': menuItem.id == currentStepWizard,
							'completed-step-wizard': menuItem.stepNumber < currentStepNumber,
						}"
						@click="backToWizardStep(menuItem)">
						<span class="circle">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M470.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L192 338.7 425.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
							</svg>
						</span>
						<span class="content-item-step">
							<span class="title-step">
								{{ menuItem.text }}
							</span>
							<span class="id-step">
								<template v-if=" menuItem.stepNumber < currentStepNumber "> {{ languageApp.complete }} </template>
								<template v-else-if=" menuItem.stepNumber == currentStepNumber "> {{ languageApp.inProcess }} </template>
								<template v-else> {{ languageApp.notCompleted }} </template>
							</span>
						</span>
					</li>
					
				</ul>
			</div>

			<div class="wpfa-container-current-view-step">
				<transition 
					name="fade"
					enter-active-class="animate__animated animate__bounce" 
					leave-active-class="animate__animated animate__bounceOutDown"  >
					<component-welcome v-if=" currentStepWizard == 'welcome' "></component-welcome>
					<component-required-plugins 
						ref="componenteRequiredPlugins" 
						:list-of-required-plugins="listOfRequiredPlugins"
						v-if=" currentStepWizard == 'required-plugins' "
						@step-successfully-generated="stepSuccessfullyGenerated()"
						@udpate-data-plugin="udpateDataPlugin"
						@step-error="stepError"
						>
					</component-required-plugins>
					<component-dashboard-site 
						ref="componenteDashboardSite"
						@step-successfully-dashboard-site="stepSuccessfullyDashboardSite"
						@step-error="stepError"
						v-if=" currentStepWizard == 'dashboard-site' ">
					</component-dashboard-site>
					<component-dashboard-design 
						ref="componenteDashboardDesign"
						v-if=" currentStepWizard == 'dashboard-design' "
						:templates-elementor="listOfElementorTemplates"
						@step-successfully-generated="stepSuccessfullyGeneratedDashboardDesign"
						@step-error="stepError">
					</component-dashboard-design>
					<component-dashboard-menu 
						ref="componenteDashboardMenu"
						v-if=" currentStepWizard == 'dashboard-menu' "
						@step-successfully-generated="successfulMenuCreationStep"
						@step-error="stepError">
					</component-dashboard-menu>
					<component-done 
						:completed-step-in-wizard=" wizardCompleted "
						v-if=" currentStepWizard == 'done' ">
						<template>
							<div class="wpfa-description-progress-bar">
								<p v-if=" !wizardCompleted ">
									{{ languageApp.theProcessMayTakeAFewMinutesPleaseDoNotCloseThisPage }}
								</p>
								<div v-else>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M243.8 339.8C232.9 350.7 215.1 350.7 204.2 339.8L140.2 275.8C129.3 264.9 129.3 247.1 140.2 236.2C151.1 225.3 168.9 225.3 179.8 236.2L224 280.4L332.2 172.2C343.1 161.3 360.9 161.3 371.8 172.2C382.7 183.1 382.7 200.9 371.8 211.8L243.8 339.8zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/></svg>
									<p>{{ languageApp.processSuccessfullyCompleted }}</p>
								</div>
							</div>

							<button  
								v-if=" wizardCompleted" 
								class="btn-wpfa-wizard wpfa-color-primary"
								@click="goToAllPages">
								{{ languageApp.goToPages }}
							</button>

						</template>
					</component-done>
				</transition>
	
				<button 
					:disabled="disabledButtons" 
					@click=" nextViewWizard() " 
					class="btn-wpfa-wizard wpfa-color-primary button-step-next-wizard" 
					v-if=" currentStepWizard != 'done' ">
					{{ languageApp.next }}
				</button>
				<button 
					:disabled="disabledButtons" 
					@click=" backViewWizard() " 
					class="btn-wpfa-wizard wpfa-color-secondary" 
					v-if=" currentStepWizard != 'welcome' && currentStepWizard != 'done'">
					{{ languageApp.back }}
				</button>
			</div>

		</div>
	</div>
</div>


<!-- COMPONENT TEMPLATE -->

<!-- Componente: Welcome -->
<script type="text/x-template" id="wpfa-component-welcome">
	<div class="wpfa-container-component-welcome">
		<h2 class="wpfa-title-steps-component">{{ languageApp.welcome }}</h2>
		<p 
			v-html="languageApp.welcomeToWpfaTemplateImporterWeWillGuideYouStepByStepToCreateAFrontendDashboardForWordpressUsingElementorTemplates"
			class="wpfa-description-steps-component">
		</p>
		<p
			v-html="languageApp.noteForTheTemplatesImportedWithThisPluginToWorkProperlyYouMustHaveTheWpFrontendAdminPluginInstalledAndActivatedAlongWithTheOtherPluginsWeWillShowYouInTheNextStep">
		</p>
	</div>
</script>


<!-- Componente: Required plugins -->
<script type="text/x-template" id="wpfa-component-required-plugins">
	<div class="wpfa-container-component-required-plugins">

		<div class="wpfa-actions-required-plugins">
			<div class="wpfa-title-required-plugins">
				<h2 class="wpfa-title-steps-component">{{ languageApp.requiredPlugins }}</h2>
				<p class="wpfa-description-steps-component">{{ languageApp.theFollowingPluginsAreRequiredSoPleaseInstallAndOrActivateThemBelow }}</p>
			</div>
			<div class="wpfa-actions-buttons">
				<button 
					v-if="showButtonAllInstallPlugins"
					:disabled="disabledAllButtons"
					@click="installAllPlugins"
					class="button button-primary">
					{{ languageApp.installAllPlugins }}
				</button>
			</div>
		</div>
		

		<div class="wpfa-container-list-plugins">
			<div class="wpfa-row-list-plugins">
				<div 
					class="wpfa-item-plugin" 
					v-for="itemPluginRequired in listPlugins"
					:style="'background:' + itemPluginRequired.colorPlugin ">
					<div class="wpfa-container-plugin">
						<img class="wpfa-plugin-logo-required" :src="itemPluginRequired.urlImage " alt="">
						<div class="wpfa-content-plugin">
							<div class="wpfa-content-plugin-details">
								<h6 class="wpfa-title-plugin-required">{{ itemPluginRequired.name }}</h6>
								<p class="wpfa-link-plugin-required">by <a :href="itemPluginRequired.linkAuthor" target="_blank">{{ itemPluginRequired.author }}</a></p>
								<p class="wpfa-description-plugin-required">
									{{ itemPluginRequired.description }}
								</p>
								<p  class="wpfa-description-plugin-required" v-if=" itemPluginRequired.slug === 'wp-menu-icons' ">
									* {{ languageApp.installThisPluginOrThePluginMenuIcons }}
								</p>
								<p  class="wpfa-description-plugin-required" v-if=" itemPluginRequired.slug === 'menu-icons' ">
									* {{ languageApp.installThisPluginOrThePluginWpMenuIcons }}
								</p>
							</div>

							

							<div class="wpfa-actions-footer-plugin">
								<a class="wpfa-see-more-plugin-required" :href="itemPluginRequired.linkPlugin" target="_blank">{{ languageApp.seeMore }}</a>
								<div>
									<button  
										v-if=" itemPluginRequired.status === 'not-install' " 
										@click="installPlugin(itemPluginRequired.slug, itemPluginRequired)"
										class="button button-primary"
										:disabled="itemPluginRequired.animation || disabledAllButtons">
										<span v-if="!itemPluginRequired.animation">
											{{ languageApp.installAndActivate }}
										</span>
										<div v-if="itemPluginRequired.animation" class="wpfa-content-animation-button">
											<span class="loader"></span>
										</div>
									</button>
									<button  
										v-else-if=" !itemPluginRequired.isActive "
										@click="activateWpPlugin(itemPluginRequired.slug, itemPluginRequired)"
										class="button button-primary"
										:disabled="itemPluginRequired.animation || disabledAllButtons">
										<span v-if="!itemPluginRequired.animation">
											{{ languageApp.activate }}
										</span>
										<div v-if="itemPluginRequired.animation" class="wpfa-content-animation-button">
											<span class="loader"></span>
										</div>
									</button>
									<span 
                                        v-else-if=" itemPluginRequired.status === 'install' "
                                        class="wpfa-installation-details-plugin-required">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                        {{ languageApp.installedAndActivated }}
                                    </span>
									
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<p 
			v-html="languageApp.noteToAddMenuIconsToTheDashboardYouCanInstallWhetherWpMenuIconsByQuadlayersOrMenuIconsByThemeisleToAvoidAnyConflictsWeRecommendYouToUseOneOptionOnly"
			class="wpfa-description-steps-component wpfa-description-note-footer-required-plugins">
		
		</p>
	</div>
</script>

<!-- Componente: Dashboard site -->
<script type="text/x-template" id="wpfa-component-dashboard-site">
	<div class="wpfa-container-component-dashboard-site">
		<h2 class="wpfa-title-steps-component">{{ languageApp.dashboardSite }}</h2>
		<p class="wpfa-description-steps-component">{{ languageApp.pleaseSelectTheSiteWhereTheConfigurationsWillBeApplied }}</p>

		<div class="wpfa-select-dashboard-site">
			<template v-if="!isSearchable">
				<vue-multiselect 
					v-model="selected" 
					:options="optionsSubdomains" 
					:placeholder="languageApp.enterTheNameOfTheSiteToSearch" 
					label="name" 
					track-by="name"
					:searchable="false"
					:options-limit="300" 
					:multiple="false" 
					:loading="isLoading" 
					:max-height="600" 
					:limit="11">
					<span slot="noResult">
						{{ languageApp.noOptionThatMatchesTheQuery }}
					</span>
					<span slot="noOptions">
						{{ languageApp.noOptionAvailableWithThisName }}
					</span>
				</vue-multiselect>
			</template>
			
			<template v-if="isSearchable">
				<vue-multiselect 
					v-model="selected" 
					:options="optionsSubdomains" 
					:placeholder="languageApp.enterTheNameOfTheSiteToSearch" 
					label="name" 
					track-by="name"
					:searchable="true"
					:options-limit="300" 
					:multiple="false" 
					:loading="isLoading" 
					:max-height="600" 
					:limit="11"
					@search-change="searchSiteMultisite">
					<span slot="noResult">
						{{ languageApp.noOptionThatMatchesTheQuery }}
					</span>
					<span slot="noOptions">
						{{ languageApp.noOptionAvailableWithThisName }}
					</span>
				</vue-multiselect>
			</template>
		</div>
	</div>
</script>

<!-- Componente: Dashboard design -->
<script type="text/x-template" id="wpfa-component-dashboard-design">
	<div class="wpfa-container-component-dashboard-design">
		<!-- lightbox templates elementor -->
		<transition 
			name="fade"
			enter-active-class="animate__animated animate__zoomInUp" 
			leave-active-class="animate__animated animate__zoomOut"  >
			<div v-show="showLightbox" @click="closeLightbox($event)" class="wpfa-lightbox-templates">
				<div class="wpfa-modal-content-lightbox">
					<button @click="notShowImageTemplateInLightbox()" class="wpfa-close-lightbox">
						<img :src="urlBasePlugin + 'assets/imgs/fontawesome/xmark-solid.svg'">
					</button>
					<img :src="imgSrcLightbox">            
				</div>
			</div>
		</transition>

		<h2 class="wpfa-title-steps-component">{{ languageApp.dashboardDesign }}</h2>
		<p class="wpfa-description-steps-component">{{ languageApp.selectTheTemplateYouWantToUseToBuildYourFrontendDashboard }}</p>

		<div class="wpfa-container-list-templates">
			<div 
				v-for="template in templatesDesign"
				:class="{'wpfa-select-template-design': template.idTemplate === selectTemplate}"
				class="wpfa-item-list-template">
				<p class="wpfa-title-template">{{ template.titleTemplate }}</p> 
				<img @click="showImageTemplateInLightbox(template)" :src="template.urlImage" alt="">
				<button @click="selectTemplateDesign(template.idTemplate)" class="btn-wpfa-wizard wpfa-color-secondary">{{ languageApp.selectTemplate }}</button>
			</div>
		</div>
	</div>
</script>


<!-- Componente: Dashboard menu -->
<script type="text/x-template" id="wpfa-component-dashboard-menu">
	<div class="wpfa-container-component-dashboard-menu">
		<h2 class="wpfa-title-steps-component">{{ languageApp.dashboardPages }}</h2>
		<p class="wpfa-description-steps-component">{{ languageApp.belowYouCanSeeAllTheWpAdminPagesFromYourDashboardSelectThePagesYouWantToAddToTheFrontendDashboardYouCanUseTheDropdownToSelectUnselectSpecificPages }}</p>

		<div v-if="showAnimationMenuItems" class="container-animation-loading-menu-items">
			<div class="lds-ellipsis">
				<div></div>
				<div></div>
				<div></div>
				<div></div>
			</div>
		</div>

		<div class="wpfa-list-menu-site">
			<div
				v-for="(itemMenu , index) in listOfSiteMenu"
				class="wpfa-container-item-menu-site">

				<div class="wpfa-item-menu-site" @click="changeViewOfChildItems(itemMenu)">
					<div class="wpfa-item-menu-info">
						<label class="wpfa-field-item-menu" :for="'item-menu-parent'+itemMenu.id">
							<input 
								:id="'item-menu-parent'+itemMenu.id" 
								:name="'item-menu-parent'+itemMenu.id" 
								type="checkbox" 
								v-model="itemMenu.checkMenu"
								@change="changeInMenuParentItem(itemMenu.checkMenu, itemMenu)">
							<span class="wpfa-text-item-menu">{{ itemMenu.menuName }}</span>
						</label>
						<a :href="urlAdmin + itemMenu.menuLink" target="_blank" class="wpfa-link-item-menu">
							<img :src="urlBasePlugin + 'assets/imgs/fontawesome/arrow-up-right-from-square-solid.svg'" alt="">
						</a>
						<a href="#" v-show="itemMenu.toggleMenu" class="button-actions-childs-items" @click="selectAllChildsMenu(event, itemMenu)">
							{{ languageApp.selectAll }}
						</a>
						<a href="#" v-show="itemMenu.toggleMenu" class="button-actions-childs-items" @click="unselectAllChildsMenu(event, itemMenu)">
							{{ languageApp.unselectAll }}
						</a>
					</div>
					<div v-show="itemMenu.childs.length > 0" class="wpfa-item-menu-actions">
						<button class="wpfa-toggle-item-menu">
							<img v-show="!itemMenu.toggleMenu" :src="urlBasePlugin + 'assets/imgs/fontawesome/sort-down-solid.svg'">
							<img v-show="itemMenu.toggleMenu" :src="urlBasePlugin + 'assets/imgs/fontawesome/sort-up-solid.svg'">
						</button>
					</div>
				</div>
				
				<!-- Template child menu -->
				<div 
					v-show="itemMenu.toggleMenu"
					class="wpfa-container-childs-menu">
					<div 
						v-for="(childMenu, indexChild) in itemMenu.childs"
						class="wpfa-item-menu-site wpfa-item-child-menu">
						<label class="wpfa-field-item-menu" :for="'item-menu-submenu-'+childMenu.id">
							<input 
								:id="'item-menu-submenu-'+childMenu.id" 
								:name="'item-menu-submenu-'+childMenu.id" 
								type="checkbox"
								v-model="childMenu.checkMenu"
								@change="changeCheckboxChild(childMenu.checkMenu, itemMenu)">
							<span class="wpfa-text-item-menu">{{ childMenu.menuName }}</span>
						</label>
						<a :href="urlAdmin + childMenu.menuLink" target="_blank" class="wpfa-link-item-menu">
							<img :src="urlBasePlugin + 'assets/imgs/fontawesome/arrow-up-right-from-square-solid.svg'" alt="">
						</a>
					</div>
				</div>

			</div>

		</div>
	</div>
</script>


<!-- Componente: Done -->
<script type="text/x-template" id="wpfa-component-done">
	<div class="wpfa-container-component-done">
		<div 
			v-if="!completedStepInWizard" 
			class="wpfa-container-steps-done">
			<h2 class="wpfa-title-steps-component">{{ languageApp.import }}</h2>
			<p class="wpfa-description-steps-component">
				{{ languageApp.waitWhileWeCompleteTheFollowingProcesses }}
			</p>
			<ol>
				<li>{{ languageApp.createANavigationMenuForYourFrontendDashboard }}</li>
				<li>{{ languageApp.buildTheFrontendDashboardPagesYouSelected }}</li>
				<li>{{ languageApp.automaticallyAddMenuIconsToYourFrontendDashboardMenu }}</li>
			</ol>
		
			<div class="wpfa-done-progress-bar">
				<p class="wpfa-title-progress-bar">{{ languageApp.pleaseWaitWhileWeFinishConfiguringEverything }}</p>
				<div class="wpfa-container-progress-bar">
					<div class="wpfa-container-progress-bar-div">
						<progress id="file" max="100" value="100"></progress>
					</div>
				</div>
				
				<!-- Text section showing that the process is in progress and button to exit the wizard when the steps have been completed. -->
				<slot></slot>
			</div>
		</div>
		<div 
			v-if="completedStepInWizard"  
			class="wpfa-container-steps-completed">
			<slot></slot>
		</div>
	</div>
</script>

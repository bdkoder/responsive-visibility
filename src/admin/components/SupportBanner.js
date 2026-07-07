import { Button, Card, CardBody } from '@wordpress/components';
import profilePhoto from '../../../assets/imgs/600x600-56KB.jpg';

export default function SupportBanner( { strings } ) {
	const whatsappNumber = strings.supportWhatsApp.replace( /\D/g, '' );

	return (
		<Card className="rv-admin__support-banner">
			<CardBody>
				<div className="rv-admin__support-grid">
					<div className="rv-admin__support-avatarWrap">
						<img
							className="rv-admin__support-avatar"
							src={ profilePhoto }
							alt={ strings.supportName }
							loading="lazy"
							width="72"
							height="72"
						/>
					</div>
					<div className="rv-admin__support-copy">
						<p className="rv-admin__support-kicker">
							{ strings.supportTitle }
						</p>
						<h2>
							{ strings.supportName }
							<span className="rv-admin__support-separator">
								{ ' | ' }
							</span>
							<span className="rv-admin__support-role">
								{ strings.supportRole }
							</span>
						</h2>
						<p className="rv-admin__support-bio">
							{ strings.supportIntro } { strings.supportBio }
						</p>
					</div>
					<div className="rv-admin__support-actions">
						<Button
							variant="primary"
							href={ `mailto:${ strings.supportEmail }` }
						>
							{ strings.supportEmail }
						</Button>
						<Button
							variant="secondary"
							href={ `https://wa.me/${ whatsappNumber }` }
							target="_blank"
							rel="noreferrer"
						>
							{ `WhatsApp: ${ strings.supportWhatsApp }` }
						</Button>
						<Button
							variant="tertiary"
							href="https://wowdevs.com/"
							target="_blank"
							rel="noreferrer"
						>
							{ strings.supportWebsite }
						</Button>
					</div>
				</div>
			</CardBody>
		</Card>
	);
}

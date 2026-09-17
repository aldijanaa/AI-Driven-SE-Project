-- Expands the destination catalog from 16 to 26, weighted toward
-- budget-tier options (6 of the 10 new entries) and new countries not yet
-- represented (South America, the Caucasus, the Balkans, Oceania).

INSERT INTO destinations (
    name, country, budget_level, budget_min, budget_max,
    best_month_start, best_month_end, temperature, stay_min, stay_max,
    beach_score, history_score, food_score, nature_score, nightlife_score,
    adventure_score, relaxation_score, family_score, vibe_tags
) VALUES
    ('Hanoi', 'Vietnam', 'budget', 450, 700, 10, 12, 24, 5, 8,
        2, 9, 9, 5, 6, 7, 4, 5, '{modest_history,budget_friendly,food}'),
    ('Lima', 'Peru', 'budget', 500, 800, 6, 9, 19, 4, 6,
        5, 8, 10, 6, 6, 7, 4, 5, '{food,budget_friendly,adventure}'),
    ('Tbilisi', 'Georgia', 'budget', 400, 650, 9, 10, 18, 4, 6,
        1, 9, 8, 6, 6, 5, 4, 5, '{modest_history,budget_friendly,food}'),
    ('Seville', 'Spain', 'moderate', 700, 1100, 4, 5, 22, 4, 6,
        3, 9, 9, 4, 7, 3, 6, 6, '{romance,modest_history,food}'),
    ('Krakow', 'Poland', 'budget', 400, 600, 5, 6, 18, 3, 5,
        1, 10, 6, 4, 6, 3, 4, 6, '{modest_history,budget_friendly}'),
    ('Rio de Janeiro', 'Brazil', 'moderate', 900, 1500, 12, 3, 28, 5, 8,
        10, 6, 7, 8, 9, 7, 5, 5, '{nature_escape,adventure}'),
    ('Zanzibar', 'Tanzania', 'moderate', 800, 1300, 6, 10, 27, 5, 8,
        10, 3, 6, 7, 3, 5, 9, 6, '{romance,nature_escape}'),
    ('Medellín', 'Colombia', 'budget', 500, 800, 12, 2, 22, 5, 8,
        2, 5, 6, 7, 8, 7, 5, 5, '{nature_escape,adventure,budget_friendly}'),
    ('Queenstown', 'New Zealand', 'luxury', 1300, 2000, 12, 2, 20, 4, 6,
        2, 2, 6, 10, 5, 10, 6, 5, '{nature_escape,adventure,luxury}'),
    ('Split', 'Croatia', 'budget', 500, 800, 6, 9, 26, 4, 7,
        9, 8, 6, 5, 7, 4, 6, 6, '{budget_friendly,food,nightlife}')
ON CONFLICT (name, country) DO NOTHING;

INSERT INTO destination_knowledge (destination_id, tags, body)
SELECT d.id, k.tags, k.body
FROM (VALUES
    ('Hanoi', 'Vietnam', ARRAY['food'],
        'The Old Quarter''s street food stalls, many run by the same family for generations, serve bowls of pho and bun cha for a fraction of restaurant prices.'),
    ('Hanoi', 'Vietnam', ARRAY['history'],
        'The Temple of Literature, Vietnam''s first national university founded in 1070, still hosts graduation ceremonies where students touch the stone stelae for luck.'),
    ('Hanoi', 'Vietnam', ARRAY['budget'],
        'A full day of exploring the Old Quarter, including meals and coffee breaks at sidewalk cafes, typically costs less than a single meal in most Western European capitals.'),

    ('Lima', 'Peru', ARRAY['food'],
        'Lima is widely considered South America''s culinary capital, home to several restaurants that regularly rank among the World''s 50 Best.'),
    ('Lima', 'Peru', ARRAY['history'],
        'The historic center''s Spanish colonial architecture, centered on the Plaza Mayor, earned it UNESCO World Heritage status in 1988.'),
    ('Lima', 'Peru', ARRAY['budget'],
        'Despite the city''s international food reputation, a filling ceviche lunch at a local cevicheria rarely costs more than a few euros.'),

    ('Tbilisi', 'Georgia', ARRAY['history'],
        'The Narikala Fortress has overlooked the city since the 4th century, surviving Persian, Arab, and Mongol invasions.'),
    ('Tbilisi', 'Georgia', ARRAY['food'],
        'Georgian supra feasts, built around khachapuri and wine from some of the world''s oldest winemaking regions, are as much a social ritual as a meal.'),
    ('Tbilisi', 'Georgia', ARRAY['budget'],
        'A sit-down supra with wine for two rarely costs more than a casual dinner for one back home, and the city''s marshrutka minibuses make getting around cheap too.'),

    ('Seville', 'Spain', ARRAY['history'],
        'The Alcazar of Seville, still used by the Spanish royal family today, is one of the oldest royal palaces still in use in Europe.'),
    ('Seville', 'Spain', ARRAY['food'],
        'Tapas culture originated in Andalusia, and many of Seville''s traditional bars still serve a free bite with every drink.'),
    ('Seville', 'Spain', ARRAY['relaxation'],
        'Plan around the brutal July-August heat; spring''s orange blossom season and the cooler evenings of October are when the city is at its most pleasant.'),

    ('Krakow', 'Poland', ARRAY['history'],
        'Krakow''s medieval Old Town was among the first UNESCO World Heritage Sites ever listed, its layout barely changed since the 13th century.'),
    ('Krakow', 'Poland', ARRAY['food'],
        'The Main Square''s milk bars, a holdover from the communist era, still serve hearty Polish meals for a few euros.'),
    ('Krakow', 'Poland', ARRAY['budget'],
        'Krakow is consistently ranked among Europe''s most affordable city breaks, with local beer, transit, and meals priced well below Western European capitals.'),

    ('Rio de Janeiro', 'Brazil', ARRAY['beach'],
        'Copacabana and Ipanema aren''t just tourist beaches - they''re the city''s living room, where cariocas play footvolley and watch the sunset every evening.'),
    ('Rio de Janeiro', 'Brazil', ARRAY['nature'],
        'Tijuca Forest, one of the world''s largest urban rainforests, surrounds the Christ the Redeemer statue and is home to howler monkeys and toucans.'),
    ('Rio de Janeiro', 'Brazil', ARRAY['nightlife'],
        'Lapa''s samba clubs and street parties happen every night of the week, not just during Carnival.'),

    ('Zanzibar', 'Tanzania', ARRAY['beach'],
        'Nungwi and Kendwa beaches on the northern tip see almost no tidal swing, so the water stays swimmable at any hour, unlike much of the island''s east coast.'),
    ('Zanzibar', 'Tanzania', ARRAY['history'],
        'Stone Town''s narrow alleys and carved wooden doors reflect centuries as a hub of the Swahili, Arab, and Indian Ocean spice trade.'),
    ('Zanzibar', 'Tanzania', ARRAY['relaxation'],
        'Spice plantation tours aside, the pace here is genuinely slow - most beach lodges have no real nightlife, and that''s the point.'),

    ('Medellín', 'Colombia', ARRAY['nature'],
        'The Metrocable gondola system, originally built to connect poorer hillside neighborhoods, doubles as one of the best, and cheapest, viewpoints over the city.'),
    ('Medellín', 'Colombia', ARRAY['nightlife'],
        'El Poblado''s Parque Lleras area turns into one of South America''s busiest nightlife strips after dark, without the price tag of similar scenes in Europe.'),
    ('Medellín', 'Colombia', ARRAY['budget'],
        'Medellin is known for its year-round mild climate, so there are no seasonal price swings, and a full dinner out rarely costs more than a coffee and pastry back home.'),

    ('Queenstown', 'New Zealand', ARRAY['adventure'],
        'Queenstown is the birthplace of commercial bungy jumping, and the original Kawarau Bridge site is still operating today.'),
    ('Queenstown', 'New Zealand', ARRAY['nature'],
        'The Remarkables mountain range drops almost straight into Lake Wakatipu, giving the town one of the most dramatic settings of any ski destination.'),
    ('Queenstown', 'New Zealand', ARRAY['family'],
        'Gentler activities like the Skyline Gondola and luge track sit right above town, making it easy to mix adrenaline with options for younger travelers.'),

    ('Split', 'Croatia', ARRAY['history'],
        'Diocletian''s Palace isn''t a museum to walk through - it''s the living core of the city, with shops, bars, and homes built directly into the 4th-century Roman walls.'),
    ('Split', 'Croatia', ARRAY['beach'],
        'Bacvice beach sits a five-minute walk from the palace and is shallow enough that picigin, a local water game, has been played there since the early 1900s.'),
    ('Split', 'Croatia', ARRAY['budget'],
        'Split makes a cheaper, less crowded base than Dubrovnik for exploring the Dalmatian coast, with frequent ferries to nearby islands like Hvar and Brac.')
) AS k(dest_name, dest_country, tags, body)
JOIN destinations d ON d.name = k.dest_name AND d.country = k.dest_country
WHERE NOT EXISTS (
    SELECT 1 FROM destination_knowledge dk WHERE dk.destination_id = d.id AND dk.body = k.body
);

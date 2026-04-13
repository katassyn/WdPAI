-- 1. Users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT '',
    role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'creator', 'user')),
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'pending', 'suspended')),
    daily_calories INT DEFAULT 2400,
    daily_protein INT DEFAULT 160,
    daily_carbs INT DEFAULT 250,
    daily_fats INT DEFAULT 70,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- 2. Recipes
CREATE TABLE recipes (
    id SERIAL PRIMARY KEY,
    creator_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    image VARCHAR(500) DEFAULT NULL,
    category VARCHAR(20) NOT NULL DEFAULT 'dinner' CHECK (category IN ('breakfast', 'lunch', 'dinner', 'snack', 'dessert')),
    difficulty VARCHAR(20) NOT NULL DEFAULT 'easy' CHECK (difficulty IN ('easy', 'medium', 'hard')),
    prep_time INT DEFAULT 0,
    cook_time INT DEFAULT 0,
    servings INT DEFAULT 2,
    moderation_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (moderation_status IN ('pending', 'approved', 'rejected', 'flagged')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. Ingredients
CREATE TABLE ingredients (
    id SERIAL PRIMARY KEY,
    recipe_id INT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    amount VARCHAR(50) DEFAULT '',
    unit VARCHAR(50) DEFAULT '',
    sort_order INT DEFAULT 0
);

-- 4. Cooking steps
CREATE TABLE cooking_steps (
    id SERIAL PRIMARY KEY,
    recipe_id INT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    step_number INT NOT NULL,
    title VARCHAR(255) DEFAULT '',
    instructions TEXT NOT NULL,
    duration_minutes INT DEFAULT 0
);

-- 5. Nutrition facts (1:1 with recipe)
CREATE TABLE nutrition_facts (
    id SERIAL PRIMARY KEY,
    recipe_id INT UNIQUE NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    calories INT DEFAULT 0,
    protein DECIMAL(6,1) DEFAULT 0,
    carbs DECIMAL(6,1) DEFAULT 0,
    fats DECIMAL(6,1) DEFAULT 0,
    fiber DECIMAL(6,1) DEFAULT 0,
    sugar DECIMAL(6,1) DEFAULT 0
);

-- 6. Tags
CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- 7. Recipe <-> Tags (M:N)
CREATE TABLE recipe_tags (
    recipe_id INT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    tag_id INT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (recipe_id, tag_id)
);

-- 8. Favorites
CREATE TABLE favorites (
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recipe_id INT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id)
);

-- 9. Daily tracking
CREATE TABLE daily_tracking (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    calories INT DEFAULT 0,
    protein DECIMAL(6,1) DEFAULT 0,
    carbs DECIMAL(6,1) DEFAULT 0,
    fats DECIMAL(6,1) DEFAULT 0,
    UNIQUE (user_id, date)
);

-- 10. Activity log
CREATE TABLE activity_log (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user (password: admin123)
INSERT INTO users (username, email, password, role, status, bio)
VALUES (
    'admin',
    'admin@macrocook.com',
    '$2y$10$9bhxVj3rFRwAsHP0FtTqb.YB.C2YA12Buk4Evhn91.nbgE1rabiMW',
    'admin',
    'active',
    'MacroCook administrator'
);

-- Regular user: Alex Johnson (password: alex123)
INSERT INTO users (username, email, password, role, status, bio, daily_calories, daily_protein, daily_carbs, daily_fats)
VALUES (
    'alex',
    'alex@example.com',
    '$2y$10$Uw9bdqolubZ7mjXVO1D7ZeJk/FPSBW3i7FxiBP5zvNR5HwCxw9Bea',
    'creator',
    'active',
    'Home cook passionate about healthy meals and macro tracking.',
    2400, 160, 250, 70
);

-- Content creator: Emma R. (password: emma123)
INSERT INTO users (username, email, password, role, status, bio)
VALUES (
    'emma',
    'emma@example.com',
    '$2y$10$qpRWan0G2cN4FcVsaONOw.2HlwUTNkNFT1/jv.tnOv1S3ZMwK0DRy',
    'creator',
    'active',
    'Professional chef sharing Mediterranean recipes.'
);

-- Pending user
INSERT INTO users (username, email, password, role, status)
VALUES (
    'newbie',
    'newbie@example.com',
    '$2y$10$fQWyl01T.PU8GybjAeNs8.65QgFSQTZiYulAslCQIAZqUpE1e4Sbe',
    'user',
    'pending'
);

-- Suspended user
INSERT INTO users (username, email, password, role, status)
VALUES (
    'banned_user',
    'banned@example.com',
    '$2y$10$Ewp8zqddMnS0Cmr/ajhbIe04cocg87A3XWCNyw9Bh46B.tCa32dd6',
    'user',
    'suspended'
);

-- Tags
INSERT INTO tags (name) VALUES
    ('high-protein'),
    ('vegetarian'),
    ('low-carb'),
    ('under-30'),
    ('vegan'),
    ('gluten-free'),
    ('spicy'),
    ('keto'),
    ('fiber-rich');

-- ============================================================
-- RECIPES
-- ============================================================

-- Recipe 1: Grilled Salmon with Asparagus
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    2, 'Grilled Salmon with Asparagus',
    'A perfectly grilled salmon fillet paired with tender asparagus spears, drizzled with lemon butter sauce. Rich in omega-3 fatty acids and protein.',
    '/public/img/salmon.jpg', 'dinner', 'medium', 10, 20, 2, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (1, 'Salmon fillet', '400', 'g', 1),
    (1, 'Asparagus', '200', 'g', 2),
    (1, 'Lemon', '1', 'whole', 3),
    (1, 'Olive oil', '2', 'tbsp', 4),
    (1, 'Garlic', '3', 'cloves', 5),
    (1, 'Butter', '30', 'g', 6),
    (1, 'Salt', '', 'to taste', 7),
    (1, 'Black pepper', '', 'to taste', 8);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (1, 1, 'Prepare the salmon', 'Pat the salmon fillets dry with paper towels. Season with salt, pepper, and a drizzle of olive oil.', 5),
    (1, 2, 'Prep asparagus', 'Trim the tough ends of the asparagus. Toss with olive oil, salt, and pepper.', 3),
    (1, 3, 'Grill the salmon', 'Place salmon skin-side down on a preheated grill (medium-high). Cook for 4-5 minutes per side until internal temp reaches 145F.', 10),
    (1, 4, 'Grill asparagus', 'Place asparagus on the grill perpendicular to the grates. Cook 3-4 minutes, turning occasionally.', 4),
    (1, 5, 'Make lemon butter', 'Melt butter in a small pan, add minced garlic and lemon juice. Cook 1 minute.', 2),
    (1, 6, 'Plate and serve', 'Arrange salmon and asparagus on plates. Drizzle with lemon butter sauce. Serve immediately.', 1);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (1, 520, 42.0, 8.0, 35.0, 3.2, 2.1);

-- Recipe 2: Quinoa Superfood Salad
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    2, 'Quinoa Superfood Salad',
    'A vibrant and nutritious salad packed with quinoa, fresh vegetables, avocado, and a tangy lemon vinaigrette. Perfect for meal prep.',
    '/public/img/quinoa.jpg', 'lunch', 'easy', 15, 15, 4, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (2, 'Quinoa', '200', 'g', 1),
    (2, 'Cherry tomatoes', '150', 'g', 2),
    (2, 'Cucumber', '1', 'medium', 3),
    (2, 'Red onion', '0.5', 'medium', 4),
    (2, 'Avocado', '1', 'whole', 5),
    (2, 'Feta cheese', '80', 'g', 6),
    (2, 'Lemon juice', '3', 'tbsp', 7),
    (2, 'Olive oil', '2', 'tbsp', 8);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (2, 1, 'Cook quinoa', 'Rinse quinoa under cold water. Cook in salted boiling water for 12-15 minutes until fluffy. Let cool.', 15),
    (2, 2, 'Chop vegetables', 'Halve cherry tomatoes, dice cucumber and red onion, slice avocado.', 8),
    (2, 3, 'Make dressing', 'Whisk together lemon juice, olive oil, salt, and pepper.', 2),
    (2, 4, 'Assemble', 'Combine cooled quinoa with chopped vegetables. Crumble feta on top. Drizzle with dressing and toss gently.', 3);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (2, 380, 14.0, 42.0, 18.0, 7.5, 4.2);

-- Recipe 3: Avocado Toast with Egg
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    3, 'Avocado Toast with Egg',
    'Creamy avocado on toasted sourdough topped with a perfectly poached egg and chili flakes. A quick and satisfying breakfast.',
    '/public/img/avocado.jpg', 'breakfast', 'easy', 5, 8, 1, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (3, 'Sourdough bread', '2', 'slices', 1),
    (3, 'Avocado', '1', 'ripe', 2),
    (3, 'Eggs', '2', 'large', 3),
    (3, 'Cherry tomatoes', '6', 'halved', 4),
    (3, 'Chili flakes', '0.5', 'tsp', 5),
    (3, 'Lemon juice', '1', 'tsp', 6),
    (3, 'Salt & pepper', '', 'to taste', 7);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (3, 1, 'Toast bread', 'Toast sourdough slices until golden and crispy.', 3),
    (3, 2, 'Prepare avocado', 'Mash avocado with lemon juice, salt, and pepper.', 2),
    (3, 3, 'Poach eggs', 'Bring water to a gentle simmer with a splash of vinegar. Crack eggs in and cook 3-4 minutes.', 4),
    (3, 4, 'Assemble', 'Spread avocado on toast, top with poached eggs, cherry tomatoes, and chili flakes.', 1);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (3, 420, 18.0, 32.0, 26.0, 8.0, 3.5);

-- Recipe 4: Spicy Chicken Stir Fry
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    2, 'Spicy Chicken Stir Fry',
    'A quick and fiery stir fry with tender chicken, colorful bell peppers, and a sweet-spicy sauce. Ready in under 30 minutes.',
    '/public/img/stirfry.jpg', 'dinner', 'medium', 10, 15, 3, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (4, 'Chicken breast', '500', 'g', 1),
    (4, 'Bell peppers', '2', 'mixed', 2),
    (4, 'Soy sauce', '3', 'tbsp', 3),
    (4, 'Sriracha', '1', 'tbsp', 4),
    (4, 'Garlic', '4', 'cloves', 5),
    (4, 'Ginger', '1', 'inch', 6),
    (4, 'Sesame oil', '1', 'tbsp', 7),
    (4, 'Rice', '300', 'g', 8);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (4, 1, 'Prep chicken', 'Cut chicken into thin strips. Marinate in soy sauce and sriracha for 10 minutes.', 10),
    (4, 2, 'Cook rice', 'Cook rice according to package directions.', 12),
    (4, 3, 'Stir fry chicken', 'Heat sesame oil in a wok over high heat. Cook chicken strips 5-6 minutes until browned.', 6),
    (4, 4, 'Add vegetables', 'Add sliced peppers, garlic, and ginger. Stir fry 3-4 minutes until crisp-tender.', 4),
    (4, 5, 'Serve', 'Plate rice, top with stir fry. Garnish with sesame seeds and green onion.', 1);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (4, 580, 45.0, 52.0, 16.0, 3.8, 6.2);

-- Recipe 5: Berry Bliss Smoothie Bowl
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    3, 'Berry Bliss Smoothie Bowl',
    'A thick and creamy smoothie bowl loaded with mixed berries, banana, and crunchy granola. The perfect antioxidant-rich breakfast.',
    '/public/img/smoothie.jpg', 'breakfast', 'easy', 10, 0, 1, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (5, 'Mixed berries (frozen)', '200', 'g', 1),
    (5, 'Banana', '1', 'frozen', 2),
    (5, 'Greek yogurt', '100', 'g', 3),
    (5, 'Almond milk', '100', 'ml', 4),
    (5, 'Granola', '40', 'g', 5),
    (5, 'Honey', '1', 'tbsp', 6),
    (5, 'Chia seeds', '1', 'tbsp', 7);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (5, 1, 'Blend base', 'Blend frozen berries, banana, greek yogurt, and almond milk until thick and smooth.', 3),
    (5, 2, 'Pour', 'Pour the thick smoothie into a bowl.', 1),
    (5, 3, 'Add toppings', 'Top with granola, fresh berries, chia seeds, and a drizzle of honey.', 2);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (5, 340, 16.0, 52.0, 8.0, 9.5, 28.0);

-- Recipe 6: Seared Beef with Vegetables
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    2, 'Seared Beef with Vegetables',
    'Perfectly seared beef strips with roasted seasonal vegetables. High protein, low carb — ideal for fitness goals.',
    '/public/img/beef.jpg', 'dinner', 'hard', 15, 25, 2, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (6, 'Beef sirloin', '400', 'g', 1),
    (6, 'Broccoli', '200', 'g', 2),
    (6, 'Sweet potato', '150', 'g', 3),
    (6, 'Olive oil', '2', 'tbsp', 4),
    (6, 'Garlic', '3', 'cloves', 5),
    (6, 'Rosemary', '2', 'sprigs', 6),
    (6, 'Salt & pepper', '', 'to taste', 7);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (6, 1, 'Prep vegetables', 'Cut broccoli into florets, dice sweet potato. Toss with olive oil, salt, pepper.', 5),
    (6, 2, 'Roast vegetables', 'Spread on a baking sheet. Roast at 200C for 20 minutes.', 20),
    (6, 3, 'Season beef', 'Season beef strips with salt, pepper, and minced garlic.', 3),
    (6, 4, 'Sear beef', 'Heat a cast iron pan over high heat. Sear beef 2-3 minutes per side for medium-rare.', 6),
    (6, 5, 'Rest and serve', 'Let beef rest 3 minutes. Slice and serve over roasted vegetables with rosemary.', 4);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (6, 490, 48.0, 22.0, 24.0, 5.0, 4.8);

-- Recipe 7: Greek Yogurt Parfait
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    3, 'Greek Yogurt Parfait',
    'Layers of creamy Greek yogurt, crunchy granola, and fresh fruit. A high-protein breakfast ready in 5 minutes.',
    '/public/img/yogurt.jpg', 'breakfast', 'easy', 5, 0, 1, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (7, 'Greek yogurt', '250', 'g', 1),
    (7, 'Granola', '50', 'g', 2),
    (7, 'Blueberries', '80', 'g', 3),
    (7, 'Strawberries', '80', 'g', 4),
    (7, 'Honey', '1', 'tbsp', 5),
    (7, 'Almonds', '20', 'g', 6);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (7, 1, 'Layer yogurt', 'Add a thick layer of Greek yogurt to the bottom of a glass or bowl.', 1),
    (7, 2, 'Add granola', 'Sprinkle a generous layer of granola over the yogurt.', 1),
    (7, 3, 'Add fruit', 'Top with fresh blueberries and sliced strawberries.', 1),
    (7, 4, 'Finish', 'Drizzle honey over the top and scatter sliced almonds.', 1);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (7, 380, 24.0, 44.0, 12.0, 4.2, 22.0);

-- Recipe 8: Hearty Lentil Soup
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    2, 'Hearty Lentil Soup',
    'A warm and comforting lentil soup with carrots, celery, and aromatic spices. Vegan, high in protein and fiber.',
    '/public/img/lentil.jpg', 'lunch', 'easy', 10, 35, 4, 'approved'
);

INSERT INTO ingredients (recipe_id, name, amount, unit, sort_order) VALUES
    (8, 'Red lentils', '300', 'g', 1),
    (8, 'Carrots', '2', 'medium', 2),
    (8, 'Celery', '2', 'stalks', 3),
    (8, 'Onion', '1', 'large', 4),
    (8, 'Garlic', '4', 'cloves', 5),
    (8, 'Vegetable broth', '1', 'liter', 6),
    (8, 'Cumin', '1', 'tsp', 7),
    (8, 'Turmeric', '0.5', 'tsp', 8);

INSERT INTO cooking_steps (recipe_id, step_number, title, instructions, duration_minutes) VALUES
    (8, 1, 'Saute aromatics', 'Dice onion, carrots, celery. Saute in olive oil over medium heat 5 minutes.', 5),
    (8, 2, 'Add spices', 'Add minced garlic, cumin, and turmeric. Cook 1 minute until fragrant.', 1),
    (8, 3, 'Add lentils and broth', 'Add rinsed lentils and vegetable broth. Bring to a boil.', 5),
    (8, 4, 'Simmer', 'Reduce heat and simmer 25-30 minutes until lentils are tender.', 28),
    (8, 5, 'Season and serve', 'Season with salt and pepper. Serve with crusty bread and a squeeze of lemon.', 1);

INSERT INTO nutrition_facts (recipe_id, calories, protein, carbs, fats, fiber, sugar)
VALUES (8, 320, 22.0, 48.0, 4.0, 12.0, 6.5);

-- Pending recipe (for moderation testing)
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    3, 'Mediterranean Bowl',
    'A colorful bowl with falafel, hummus, tabbouleh, and pickled vegetables. Fresh and filling.',
    '/public/img/mediterranean.jpg', 'lunch', 'medium', 20, 15, 2, 'pending'
);

-- Flagged recipe (for moderation testing)
INSERT INTO recipes (creator_id, title, description, image, category, difficulty, prep_time, cook_time, servings, moderation_status)
VALUES (
    4, 'Mystery Casserole',
    'A casserole recipe with unclear ingredient measurements.',
    NULL, 'dinner', 'easy', 10, 30, 4, 'flagged'
);

-- ============================================================
-- RECIPE TAGS
-- ============================================================

-- Salmon: high-protein, under-30
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (1, 1), (1, 4);
-- Quinoa: vegetarian, under-30, low-carb
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (2, 2), (2, 4), (2, 3);
-- Avocado Toast: vegetarian, under-30
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (3, 2), (3, 4);
-- Stir Fry: high-protein, spicy
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (4, 1), (4, 7);
-- Smoothie: vegetarian, under-30
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (5, 2), (5, 4);
-- Beef: high-protein, low-carb, under-30
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (6, 1), (6, 3), (6, 4);
-- Yogurt: vegetarian, high-protein, under-30, low-carb
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (7, 2), (7, 1), (7, 4), (7, 3);
-- Lentil: vegetarian, high-protein, fiber-rich
INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (8, 2), (8, 1), (8, 9);

-- ============================================================
-- FAVORITES (Alex has some favorites)
-- ============================================================

INSERT INTO favorites (user_id, recipe_id) VALUES (2, 1), (2, 2), (2, 3), (2, 5);

-- ============================================================
-- DAILY TRACKING (Alex's data for today)
-- ============================================================

INSERT INTO daily_tracking (user_id, date, calories, protein, carbs, fats)
VALUES (2, CURRENT_DATE, 1850, 142, 120, 45);

-- ============================================================
-- ACTIVITY LOG
-- ============================================================

INSERT INTO activity_log (user_id, type, description, created_at) VALUES
    (2, 'cooked', 'You cooked Grilled Salmon with Asparagus', NOW() - INTERVAL '2 hours'),
    (2, 'saved', 'You saved Berry Bliss Smoothie Bowl to favorites', NOW() - INTERVAL '5 hours'),
    (2, 'created', 'You created a new recipe: Protein Pancakes', NOW() - INTERVAL '1 day'),
    (2, 'cooked', 'You cooked Quinoa Superfood Salad', NOW() - INTERVAL '1 day 3 hours'),
    (2, 'cooked', 'You cooked Avocado Toast with Egg', NOW() - INTERVAL '2 days');
